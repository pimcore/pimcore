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

namespace Pimcore\Routing;

use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Routing\Dynamic\DynamicRequestContext;
use Pimcore\Routing\Dynamic\DynamicRouteHandlerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 */
final class DynamicRouteProvider implements RouteProviderInterface
{
    protected SiteResolver $siteResolver;

    /**
     * @var DynamicRouteHandlerInterface[]
     */
    protected array $handlers = [];

    /**
     * @param DynamicRouteHandlerInterface[] $handlers
     */
    public function __construct(SiteResolver $siteResolver, array $handlers = [])
    {
        $this->siteResolver = $siteResolver;

        foreach ($handlers as $handler) {
            $this->addHandler($handler);
        }
    }

    public function addHandler(DynamicRouteHandlerInterface $handler): void
    {
        if (!in_array($handler, $this->handlers, true)) {
            $this->handlers[] = $handler;
        }
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $collection = new RouteCollection();

        if ($request->attributes->has('_controller')) {
            return $collection;
        }

        $path = $originalPath = rawurldecode($request->getPathInfo());

        // site path handled by FrontendRoutingListener which runs before routing is started
        if (null !== $sitePath = $this->siteResolver->getSitePath($request)) {
            $path = $sitePath;
        }

        foreach ($this->handlers as $handler) {
            $handler->matchRequest($collection, new DynamicRequestContext($request, $path, $originalPath));
        }

        return $collection;
    }

    public function getRouteByName(string $name): SymfonyRoute
    {
        foreach ($this->handlers as $handler) {
            try {
                return $handler->getRouteByName($name);
            } catch (RouteNotFoundException $e) {
                // noop
            }
        }

        throw new RouteNotFoundException(sprintf("Route for name '%s' was not found", $name));
    }

    public function getRoutesByNames(array $names = null): array
    {
        // TODO needs performance optimizations
        // TODO really return all routes here as documentation states? where is this used?
        $routes = [];

        if (is_array($names)) {
            foreach ($names as $name) {
                try {
                    $route = $this->getRouteByName($name);
                    $routes[] = $route;
                } catch (RouteNotFoundException $e) {
                    // noop
                }
            }
        }

        return $routes;
    }
}
