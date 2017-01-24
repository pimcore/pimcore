<?php

namespace PimcoreBundle\Routing;

use Doctrine\Common\Util\Inflector;
use Pimcore\Model\Document;
use Symfony\Cmf\Bundle\RoutingBundle\Model\RedirectRoute;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class DocumentRouteProvider implements RouteProviderInterface
{
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

        $url      = rawurldecode($request->getPathInfo());
        $document = Document::getByPath($url);

        if ($document) {
            $route = $this->buildRouteForDocument($document);

            if ($route) {
                $collection->add($route->getRouteKey(), $route);
            }
        }

        return $collection;
    }

    /**
     * @param Document|Document\Page $document
     * @return DocumentRoute
     */
    protected function buildRouteForDocument(Document $document)
    {
        $handleDocument = false;

        if ($document->getProperty('symfony')) {
            $handleDocument = true;
        }

        // TODO remove - this is just for testing. Overrides all documents with symfony mode
        // (allows to test symfony rendering without having to touch all documents)
        // controller = foo, action = bar becomes AppBundle:Foo:bar
        if (defined('PIMCORE_SYMFONY_OVERRIDE_DOCUMENTS') && PIMCORE_SYMFONY_OVERRIDE_DOCUMENTS) {
            $handleDocument = true;
        }

        if (!$handleDocument) {
            return null;
        }

        $route = new DocumentRoute($document->getRealFullPath());
        $route->setDefault('_locale', $document->getProperty('language'));
        $route->setDocument($document);

        if ($document instanceof Document\Link) {
            $route->setDefault('_controller', 'FrameworkBundle:Redirect:urlRedirect');
            $route->setDefault('path', $document->getHref());
            $route->setDefault('permanent', true);
        } else {
            $bundle     = 'AppBundle';
            $controller = 'Content';
            $action     = 'default';

            if ($document->getModule()) {
                $bundle = $document->getModule();
                if (strpos($bundle, 'Bundle') === false) {
                    $bundle = sprintf('%sBundle', $bundle);
                }
            }

            if ($document->getController()) {
                $controller = ucfirst($document->getController());
            }

            if ($document->getAction()) {
                $action = $document->getAction();
                $action = Inflector::camelize($action);
            }

            $route->setDefault('_controller', implode(':', [$bundle, $controller, $action]));
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

            if ($document && $document->getProperty('symfony')) {
                return $this->buildRouteForDocument($document);
            }
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
}
