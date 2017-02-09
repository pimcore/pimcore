<?php

namespace Pimcore\Bundle\PimcoreBundle\Routing;

use Doctrine\Common\Util\Inflector;
use Pimcore\Bundle\PimcoreBundle\Service\Document\DocumentService;
use Pimcore\Bundle\PimcoreBundle\Service\MvcConfigNormalizer;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Model\Staticroute;
use Pimcore\Model\Staticroute\Listing;
use Pimcore\Tool;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class DocumentRouteProvider implements RouteProviderInterface
{
    /**
     * @var DocumentService
     */
    protected $documentService;

    /**
     * @var MvcConfigNormalizer
     */
    protected $configNormalizer;

    /**
     * @param DocumentService $documentService
     */
    public function __construct(DocumentService $documentService, MvcConfigNormalizer $configNormalizer)
    {
        $this->documentService  = $documentService;
        $this->configNormalizer = $configNormalizer;
    }

    /**
     * Finds routes that may potentially match the request.
     *
     * This may return a mixed list of class instances, but all routes returned
     * must extend the core symfony route. The classes may also implement
     * RouteObjectInterface to link to a content document.
     *
     * This method may not throw an exception based on implementation specific
     * restrictions on the url. That case is considered a not found - returning
     * an empty array. Exceptions are only used to abort the whole request in
     * case something is seriously broken, like the storage backend being down.
     *
     * Note that implementations may not implement an optimal matching
     * algorithm, simply a reasonable first pass.  That allows for potentially
     * very large route sets to be filtered down to likely candidates, which
     * may then be filtered in memory more completely.
     *
     * @param Request $request A request against which to match.
     *
     * @return RouteCollection with all Routes that could potentially match
     *                         $request. Empty collection if nothing can match.
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        $collection = new RouteCollection();

        $path = $originalPath = urldecode($request->getPathInfo());

        // handled by SiteListener which runs before routing is started
        if ($request->attributes->has('_site_path')) {
            $path = $request->attributes->get('_site_path');
        }

        $document = Document::getByPath($path);
        if ($document) {
            $route = $this->buildRouteForDocument($document);

            if ($route) {
                $collection->add($route->getRouteKey(), $route);
            }
        } else {
            // TODO this might not be the best place, but works for now
            $this->handleStaticRoute($collection, $request, $originalPath);
        }

        return $collection;
    }

    /**
     * @param Document|Document\Page $document
     * @return DocumentRoute
     */
    protected function buildRouteForDocument(Document $document)
    {
        if (!$this->handleDocument($document)) {
            return null;
        }

        $locale = $document->getProperty('language');
        $locale = explode('_', $locale);
        $locale = $locale[0];

        $route = new DocumentRoute($document->getRealFullPath());
        $route->setDefault('_locale', $locale);
        $route->setDocument($document);

        if ($document instanceof Document\Link) {
            // TODO use RedirectRoute?
            $route->setDefault('_controller', 'FrameworkBundle:Redirect:urlRedirect');
            $route->setDefault('path', $document->getHref());
            $route->setDefault('permanent', true);
        } else {
            $controller = $this->configNormalizer->formatController(
                $document->getModule(),
                $document->getController(),
                $document->getAction()
            );

            $route->setDefault('_controller', $controller);

            if ($document->getTemplate()) {
                $template = $this->configNormalizer->normalizeTemplate($document->getTemplate());
                $route->setDefault('_template', $template);
            }
        }

        return $route;
    }

    /**
     * @param RouteCollection $collection
     * @param Request $request
     * @param $path
     */
    protected function handleStaticRoute(RouteCollection $collection, Request $request, $path)
    {
        $staticRoutes = $this->getStaticRoutes();

        $routingDefaults = Tool::getRoutingDefaults();
        $params          = array_merge($routingDefaults, $request->request->all());

        foreach ($staticRoutes as $staticRoute) {
            $routeParams = $staticRoute->match($path, $params);

            if ($routeParams) {
                $params = $routeParams;

                // TODO replace module with bundle in admin
                if (!$params['module'] || $params['module'] === 'website') {
                    $params['module'] = defined('PIMCORE_SYMFONY_DEFAULT_BUNDLE') ? PIMCORE_SYMFONY_DEFAULT_BUNDLE : 'AppBundle';
                }

                if ($params['controller'] && $params['action']) {
                    $route = $this->buildRouteForStaticroute($staticRoute, $path, $params);

                    $controller = $this->configNormalizer->formatController(
                        $params['module'],
                        $params['controller'],
                        $params['action']
                    );

                    $route->setDefault('_controller', $controller);

                    $collection->add($staticRoute->getName(), $route);
                }

                Staticroute::setCurrentRoute($staticRoute);

                // TODO omitted pimcore_request_source

                break;
            }
        }
    }

    /**
     * @param Staticroute $staticRoute
     * @param string $path
     * @param array $params
     * @return StaticrouteRoute
     */
    protected function buildRouteForStaticroute(Staticroute $staticRoute, $path = '/', array $params = [])
    {
        $route = new StaticrouteRoute($path);
        $route->setStaticRoute($staticRoute);

        // add matching params to route
        foreach ($params as $key => $val) {
            if (!in_array($key, ['module', 'controller', 'action'])) {
                $route->setDefault($key, $val);
            }
        }

        return $route;
    }

    /**
     * Find the route using the provided route name.
     *
     * @param string $name The route name to fetch.
     *
     * @return Route
     *
     * @throws RouteNotFoundException If there is no route with that name in
     *                                this repository
     */
    public function getRouteByName($name)
    {
        if (preg_match('/^document_(\d+)$/', $name, $match)) {
            $document = Document::getById($match[1]);

            if ($document && $this->handleDocument($document)) {
                return $this->buildRouteForDocument($document);
            }
        }

        $siteId = null;
        if (Site::isSiteRequest()) {
            $siteId = Site::getCurrentSite()->getId();
        }

        if ($staticRoute = Staticroute::getByName($name, $siteId)) {
            $route = $this->buildRouteForStaticroute($staticRoute, $staticRoute->getPattern());

            $variables      = explode(',', $staticRoute->getVariables());
            $assembleParams = [];

            foreach ($variables as $variable) {
                $variable = trim($variable);

                // inject the variable in {variable} form to get a clean path
                $assembleParams[$variable] = sprintf('{%s}', $variable);

                // we don't care about requirements on generation
                $route->setRequirement($variable, '.+');
            }

            $url = $staticRoute->assemble($assembleParams, true);
            $url = urldecode($url);

            $route->setPath($url);

            return $route;
        }

        throw new RouteNotFoundException(sprintf("Route for name '%s' was not found", $name));
    }

    /**
     * Find many routes by their names using the provided list of names.
     *
     * Note that this method may not throw an exception if some of the routes
     * are not found or are not actually Route instances. It will just return the
     * list of those Route instances it found.
     *
     * This method exists in order to allow performance optimizations. The
     * simple implementation could be to just repeatedly call
     * $this->getRouteByName() while catching and ignoring eventual exceptions.
     *
     * If $names is null, this method SHOULD return a collection of all routes
     * known to this provider. If there are many routes to be expected, usage of
     * a lazy loading collection is recommended. A provider MAY only return a
     * subset of routes to e.g. support paging or other concepts, but be aware
     * that the DynamicRouter will only call this method once per
     * DynamicRouter::getRouteCollection() call.
     *
     * @param array|null $names The list of names to retrieve, In case of null,
     *                          the provider will determine what routes to return.
     *
     * @return Route[] Iterable list with the keys being the names from the
     *                 $names array.
     */
    public function getRoutesByNames($names)
    {
        // TODO needs performance optimizations
        // TODO really return all routes here as documentation states? where is this used?
        $routes = [];

        if (is_array($names)) {
            foreach ($names as $name) {
                try {
                    $route = $this->getRouteByName($name);
                    if ($route) {
                        $routes[] = $route;
                    }
                } catch (RouteNotFoundException $e) {
                    // noop
                }
            }
        }

        return $routes;
    }

    // TODO remove - this is just for testing. Overrides all documents with symfony mode
    // (allows to test symfony rendering without having to touch all documents)
    // controller = foo, action = bar becomes AppBundle:Foo:bar
    protected function handleDocument(Document $document)
    {
        if ($document->getProperty('symfony')) {
            return true;
        }

        if (defined('PIMCORE_SYMFONY_OVERRIDE_DOCUMENTS') && PIMCORE_SYMFONY_OVERRIDE_DOCUMENTS) {
            return true;
        }

        return false;
    }

    /**
     * Load static routes
     *
     * @return Staticroute[]
     */
    protected function getStaticRoutes()
    {
        $list = new Listing();
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

        $routes = $list->load();

        return $routes;
    }
}
