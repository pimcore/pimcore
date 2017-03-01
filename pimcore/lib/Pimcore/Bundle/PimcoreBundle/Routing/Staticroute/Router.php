<?php

namespace Pimcore\Bundle\PimcoreBundle\Routing\Staticroute;

use Pimcore\Config;
use Pimcore\Model\Site;
use Pimcore\Model\Staticroute;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class Router implements RouterInterface, RequestMatcherInterface, VersatileGeneratorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var array
     */
    protected $supportedNames = [];

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var UrlMatcher
     */
    protected $matcher;

    /**
     * @var UrlGenerator
     */
    protected $generator;

    /**
     * @var Staticroute\Dao
     */
    protected $dao;

    /**
     * @param RequestContext $context
     */
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
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

    /**
     * @return Staticroute\Dao
     */
    protected function getDao()
    {
        if (null === $this->dao) {
            $this->dao = new Staticroute\Dao();
            $this->dao->configure();
        }

        return $this->dao;
    }

    /**
     * @inheritDoc
     */
    public function getRouteCollection()
    {
        if (null === $this->routes) {
            $collection   = new RouteCollection();
            $staticroutes = $this->getDao()->getAll();

            foreach ($staticroutes as $sr) {
                $this->supportedNames[] = $sr->getName();

                $siteIds = $sr->getSiteId();
                if (empty($siteIds)) {
                    $name = $this->getRouteName($sr->getName());
                    $collection->add($name, $this->buildRouteForStaticRoute($sr));
                } else {
                    foreach ($siteIds as $siteId) {
                        $name = $this->getRouteName($sr->getName(), $siteId);
                        $collection->add($name, $this->buildRouteForStaticRoute($sr, $siteId));
                    }
                }
            }

            $this->routes = $collection;
        }

        return $this->routes;
    }

    /**
     * @param string $name
     * @param int|null $siteId
     * @return string
     */
    protected function getRouteName($name, $siteId = null)
    {
        if (null === $siteId) {
            return $name;
        }

        return sprintf('%s_site_%d', $name, $siteId);
    }

    /**
     * @param Staticroute $staticRoute
     * @param int|null $siteId
     * @return Route
     */
    protected function buildRouteForStaticRoute(Staticroute $staticRoute, $siteId = null)
    {
        $route = new Route(
            $staticRoute->getPath(),
            $staticRoute->getDefaults(),
            $staticRoute->getRequirements()
        );

        if (null !== $siteId) {
            $site = Site::getById($siteId);
            $route->setHost($site->getMainDomain());

            $domains = [];
            if (!empty($site->getMainDomain())) {
                $domains[] = $site->getMainDomain();
            }

            foreach ($site->getDomains() as $domain) {
                $domains[] = $domain;
            }

            $route->setHost('{domain}');
            $route->setRequirement('domain', implode('|', $domains));
        } else {
            $route->setHost($this->context->getHost());
        }

        return $route;
    }

    /**
     * @return UrlMatcher
     */
    protected function getMatcher()
    {
        if (null === $this->matcher) {
            $this->matcher = new UrlMatcher(
                $this->getRouteCollection(),
                $this->getContext()
            );
        }

        return $this->matcher;
    }

    protected function getGenerator()
    {
        if (null === $this->generator) {
            $this->generator = new UrlGenerator(
                $this->getRouteCollection(),
                $this->getContext(),
                $this->logger
            );
        }

        return $this->generator;
    }

    /**
     * @inheritDoc
     */
    public function supports($name)
    {
        dump($name);
        die(__METHOD__);

        return is_string($name) && in_array($name, $this->supportedNames);
    }

    /**
     * @inheritDoc
     */
    public function getRouteDebugMessage($name, array $parameters = [])
    {
        return $name;
    }

    /**
     * @inheritDoc
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        $siteId = null;
        if (Site::isSiteRequest()) {
            $siteId = Site::getCurrentSite()->getId();
        }

        // check for a site in the options, if valid remove it from the options
        $domain = null;
        if (isset($parameters['site'])) {
            $config = Config::getSystemConfig();
            $site   = $parameters['site'];

            if (!empty($site)) {
                try {
                    $site = Site::getBy($site);
                    unset($parameters["site"]);

                    $domain = $site->getMainDomain();
                    $siteId = $site->getId();
                } catch (\Exception $e) {
                    $this->logger->warning('Site {site} doesn\'t exist while trying to generate route {name}', [
                        'site'      => $site,
                        'name'      => $name,
                        'exception' => $e
                    ]);
                }
            } elseif ($config->general->domain) {
                $domain = $config->general->domain;
            }
        }

        if ($domain) {
            $referenceType = static::ABSOLUTE_URL;
            $parameters['domain'] = $domain;
        }

        $routeName = $this->getRouteName($name, $siteId);

        $url = $this->generator->generate($routeName, $parameters, $referenceType);

        return $url;
    }

    /**
     * @inheritDoc
     */
    public function matchRequest(Request $request)
    {
        $result = $this->getMatcher()->matchRequest($request);

        dump($result);
        die(__METHOD__);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function match($pathinfo)
    {
        $result = $this->getMatcher()->match($pathinfo);

        dump($result);
        die(__METHOD__);

        return $result;
    }
}
