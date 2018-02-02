<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Routing\Staticroute;

use Pimcore\Config;
use Pimcore\Controller\Config\ConfigNormalizer;
use Pimcore\Model\Site;
use Pimcore\Model\Staticroute;
use Pimcore\Tool;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * A custom router implementation handling pimcore static routes.
 */
class Router implements RouterInterface, RequestMatcherInterface, VersatileGeneratorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var ConfigNormalizer
     */
    protected $configNormalizer;

    /**
     * @var Staticroute[]
     */
    protected $staticRoutes;

    /**
     * @var array
     */
    protected $supportedNames;

    /**
     * Params which are treated as _locale if no _locale attribute is set
     *
     * @var array
     */
    protected $localeParams = [];

    public function __construct(RequestContext $context, ConfigNormalizer $configNormalizer)
    {
        $this->context          = $context;
        $this->configNormalizer = $configNormalizer;
    }

    /**
     * @inheritDoc
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function getContext()
    {
        return $this->context;
    }

    public function getLocaleParams(): array
    {
        return $this->localeParams;
    }

    public function setLocaleParams(array $localeParams)
    {
        $this->localeParams = $localeParams;
    }

    /**
     * @inheritDoc
     */
    public function supports($name)
    {
        return is_string($name) && in_array($name, $this->getSupportedNames());
    }

    /**
     * @inheritDoc
     */
    public function getRouteCollection()
    {
        return new RouteCollection();
    }

    /**
     * @inheritDoc
     */
    public function getRouteDebugMessage($name, array $parameters = [])
    {
        return (string)$name;
    }

    /**
     * @inheritDoc
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        // when using $name = false we don't use the default route (happens when $name = null / ZF default behavior)
        // but just the query string generation using the given parameters
        // eg. $this->url(["foo" => "bar"], false) => /?foo=bar
        if ($name === null) {
            if (Staticroute::getCurrentRoute() instanceof Staticroute) {
                $name = Staticroute::getCurrentRoute()->getName();
            }
        }

        // ABSOLUTE_URL = http://example.com
        // NETWORK_PATH = //example.com
        $needsHostname = self::ABSOLUTE_URL === $referenceType || self::NETWORK_PATH === $referenceType;

        $siteId = null;
        if (Site::isSiteRequest()) {
            $siteId = Site::getCurrentSite()->getId();
        }

        // check for a site in the options, if valid remove it from the options
        $hostname = null;
        if (isset($parameters['site'])) {
            $config = Config::getSystemConfig();
            $site   = $parameters['site'];

            if (!empty($site)) {
                try {
                    $site = Site::getBy($site);
                    unset($parameters['site']);
                    $hostname = $site->getMainDomain();

                    if ($site->getId() !== $siteId) {
                        $needsHostname = true;
                        $siteId = $site->getId();
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('The site {site} does not exist for route {route}', [
                        'site'      => $siteId,
                        'route'     => $name,
                        'exception' => $e
                    ]);
                }
            } else {
                if ($needsHostname && !empty($config->general->domain)) {
                    $hostname = $config->general->domain;
                }
            }
        }

        if (null === $hostname && $needsHostname) {
            $hostname = $this->context->getHost();
        }

        if ($name && $route = Staticroute::getByName($name, $siteId)) {
            $reset  = isset($parameters['reset']) ? (bool)$parameters['reset'] : false;
            $encode = isset($parameters['encode']) ? (bool)$parameters['encode'] : true;

            // assemble the route / url in Staticroute::assemble()
            $url = $route->assemble($parameters, $reset, $encode);

            if ($needsHostname) {
                if (self::ABSOLUTE_URL === $referenceType) {
                    $url = $this->context->getScheme() . '://' . $hostname . $url;
                } else {
                    $url = '//' . $hostname . $url;
                }
            } else {
                if (self::RELATIVE_PATH === $referenceType) {
                    $url = UrlGenerator::getRelativePath($this->context->getPathInfo(), $url);
                }
            }

            return $url;
        }

        throw new RouteNotFoundException(sprintf('Could not generate URL for route %s as the static route wasn\'t found', $name));
    }

    /**
     * @inheritDoc
     */
    public function matchRequest(Request $request)
    {
        return $this->doMatch($request->getPathInfo());
    }

    /**
     * @inheritDoc
     */
    public function match($pathinfo)
    {
        return $this->doMatch($pathinfo);
    }

    /**
     * @param string $pathinfo
     *
     * @return array
     */
    protected function doMatch($pathinfo)
    {
        $pathinfo = urldecode($pathinfo);

        $params = $this->context->getParameters();
        $params = array_merge(Tool::getRoutingDefaults(), $params);

        foreach ($this->getStaticRoutes() as $route) {
            if ($routeParams = $route->match($pathinfo, $params)) {
                Staticroute::setCurrentRoute($route);

                // add the route object also as parameter to the request object, this is needed in
                // Pimcore_Controller_Action_Frontend::getRenderScript()
                // to determine if a call to an action was made through a staticroute or not
                // more on that infos see Pimcore_Controller_Action_Frontend::getRenderScript()
                $routeParams['pimcore_request_source'] = 'staticroute';
                $routeParams['_route']                 = $route->getName();

                $routeParams = $this->processRouteParams($routeParams);

                return $routeParams;
            }
        }

        throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $pathinfo));
    }

    /**
     * @param array $routeParams
     *
     * @return array
     */
    protected function processRouteParams(array $routeParams)
    {
        $keys = [
            'module',
            'controller',
            'action'
        ];

        $controllerParams = [];
        foreach ($keys as $key) {
            $value = null;

            if (isset($routeParams[$key])) {
                $value = $routeParams[$key];
            }

            $controllerParams[$key] = $value;
        }

        $controller = $this->configNormalizer->formatControllerReference(
            $controllerParams['module'],
            $controllerParams['controller'],
            $controllerParams['action']
        );

        $routeParams['_controller'] = $controller;

        // map common language properties (e.g. language) to _locale if not set
        if (!isset($routeParams['_locale'])) {
            foreach ($this->localeParams as $localeParam) {
                if (isset($routeParams[$localeParam])) {
                    $routeParams['_locale'] = $routeParams[$localeParam];
                    break;
                }
            }
        }

        return $routeParams;
    }

    /**
     * @return Staticroute[]
     */
    protected function getStaticRoutes()
    {
        if (null === $this->staticRoutes) {
            /** @var Staticroute\Listing|Staticroute\Listing\Dao $list */
            $list = new Staticroute\Listing();

            // do not handle legacy routes
            $list->setFilter(function (array $row) {
                if (isset($row['legacy']) && $row['legacy']) {
                    return false;
                }

                return true;
            });

            $list->setOrder(function ($a, $b) {
                // give site ids a higher priority
                if ($a['siteId'] && !$b['siteId']) {
                    return -1;
                }
                if (!$a['siteId'] && $b['siteId']) {
                    return 1;
                }

                if ($a['priority'] == $b['priority']) {
                    return 0;
                }

                return ($a['priority'] < $b['priority']) ? 1 : -1;
            });

            $this->staticRoutes = $list->load();
        }

        return $this->staticRoutes;
    }

    /**
     * @return array
     */
    protected function getSupportedNames()
    {
        if (null === $this->supportedNames) {
            $this->supportedNames = [];

            foreach ($this->getStaticRoutes() as $route) {
                $this->supportedNames[] = $route->getName();
            }

            $this->supportedNames = array_unique($this->supportedNames);
        }

        return $this->supportedNames;
    }
}
