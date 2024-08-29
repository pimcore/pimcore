<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StaticRoutesBundle\Routing\Staticroute;

use Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute;
use Pimcore\Config;
use Pimcore\Model\Site;
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
 * @internal
 *
 * A custom router implementation handling pimcore static routes.
 */
final class Router implements RouterInterface, RequestMatcherInterface, VersatileGeneratorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected RequestContext $context;

    /**
     * @var Staticroute[]|null
     */
    protected ?array $staticRoutes = null;

    /**
     * @var string[]|null
     */
    protected ?array $supportedNames = null;

    /**
     * Params which are treated as _locale if no _locale attribute is set
     *
     */
    protected array $localeParams = [];

    protected Config $config;

    public function __construct(RequestContext $context, Config $config)
    {
        $this->context = $context;
        $this->config = $config;
    }

    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    public function getContext(): RequestContext
    {
        return $this->context;
    }

    public function getLocaleParams(): array
    {
        return $this->localeParams;
    }

    public function setLocaleParams(array $localeParams): void
    {
        $this->localeParams = $localeParams;
    }

    public function getRouteCollection(): RouteCollection
    {
        return new RouteCollection();
    }

    public function getRouteDebugMessage(string $name, array $parameters = []): string
    {
        return $name;
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        if (!in_array($name, $this->getSupportedNames())) {
            throw new RouteNotFoundException('Not supported name');
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
            $site = $parameters['site'];

            if (!empty($site)) {
                if ($site = Site::getBy($site)) {
                    unset($parameters['site']);
                    $hostname = $site->getMainDomain();

                    if ($site->getId() !== $siteId) {
                        $needsHostname = true;
                        $siteId = $site->getId();
                    }
                } else {
                    $this->logger->warning('The site {site} does not exist for route {route}', [
                        'site' => $siteId,
                        'route' => $name,
                    ]);
                }
            } else {
                if ($needsHostname && !empty($this->config['general']['domain'])) {
                    $hostname = $this->config['general']['domain'];
                }
            }
        }

        if (null === $hostname && $needsHostname) {
            $hostname = $this->context->getHost();
        }

        if ($name && $route = Staticroute::getByName($name, $siteId)) {
            $encode = isset($parameters['encode']) ? (bool)$parameters['encode'] : true;
            unset($parameters['encode']);
            // assemble the route / url in Staticroute::assemble()
            $url = $route->assemble($parameters, $encode);
            $port = '';
            $scheme = $this->context->getScheme();

            if ('http' === $scheme && 80 !== $this->context->getHttpPort()) {
                $port = ':'.$this->context->getHttpPort();
            } elseif ('https' === $scheme && 443 !== $this->context->getHttpsPort()) {
                $port = ':'.$this->context->getHttpsPort();
            }

            $schemeAuthority = self::NETWORK_PATH === $referenceType || '' === $scheme ? '//' : "$scheme://";
            $schemeAuthority .= $hostname.$port;

            if ($needsHostname) {
                $url = $schemeAuthority.$this->context->getBaseUrl().$url;
            } else {
                if (self::RELATIVE_PATH === $referenceType) {
                    $url = UrlGenerator::getRelativePath($this->context->getPathInfo(), $url);
                } else {
                    $url = $this->context->getBaseUrl().$url;
                }
            }

            return $url;
        }

        throw new RouteNotFoundException(sprintf(
            'Could not generate URL for route %s as the static route wasn\'t found',
            $name
        ));
    }

    public function matchRequest(Request $request): array
    {
        return $this->doMatch($request->getPathInfo(), $request);
    }

    public function match(string $pathinfo): array
    {
        return $this->doMatch($pathinfo);
    }

    protected function doMatch(string $pathinfo, Request $request = null): array
    {
        $pathinfo = urldecode($pathinfo);

        $params = $this->context->getParameters();

        foreach ($this->getStaticRoutes() as $route) {
            if (null !== $request && null !== $route->getMethods() && 0 !== count($route->getMethods())) {
                $method = $request->getMethod();

                if (!in_array($method, $route->getMethods(), true)) {
                    continue;
                }
            }

            if ($routeParams = $route->match($pathinfo, $params)) {
                Staticroute::setCurrentRoute($route);

                // add the route object also as parameter to the request object, this is needed in
                // Pimcore_Controller_Action_Frontend::getRenderScript()
                // to determine if a call to an action was made through a staticroute or not
                // more on that infos see Pimcore_Controller_Action_Frontend::getRenderScript()
                $routeParams['pimcore_request_source'] = 'staticroute';
                $routeParams['_route'] = $route->getName();

                $routeParams = $this->processRouteParams($routeParams);

                return $routeParams;
            }
        }

        throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $pathinfo));
    }

    protected function processRouteParams(array $routeParams): array
    {
        $keys = [
            'module',
            'controller',
            'action',
        ];

        $controllerParams = [];
        foreach ($keys as $key) {
            $value = null;

            if (isset($routeParams[$key])) {
                $value = $routeParams[$key];
            }

            $controllerParams[$key] = $value;
        }

        $routeParams['_controller'] = $controllerParams['controller'];

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
    protected function getStaticRoutes(): array
    {
        if (null === $this->staticRoutes) {
            /** @var Staticroute\Listing|Staticroute\Listing\Dao $list */
            $list = new Staticroute\Listing();

            $list->setOrder(function ($a, $b) {
                // give site ids a higher priority
                if ($a->getSiteId() && !$b->getSiteId()) {
                    return -1;
                }
                if (!$a->getSiteId() && $b->getSiteId()) {
                    return 1;
                }

                if ($a->getPriority() == $b->getPriority()) {
                    return 0;
                }

                return ($a->getPriority() < $b->getPriority()) ? 1 : -1;
            });

            $this->staticRoutes = $list->load();
        }

        return $this->staticRoutes;
    }

    /**
     * @return string[]
     */
    protected function getSupportedNames(): array
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
