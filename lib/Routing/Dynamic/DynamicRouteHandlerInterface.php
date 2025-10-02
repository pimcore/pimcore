<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Routing\Dynamic;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 */
interface DynamicRouteHandlerInterface
{
    /**
     * Find the route using the provided route name.
     *
     * @param string $name The route name to fetch
     *
     * @throws RouteNotFoundException If there is no route with that name in
     *                                this repository
     */
    public function getRouteByName(string $name): ?Route;

    /**
     * Add matching routes to the route collection
     *
     */
    public function matchRequest(RouteCollection $collection, DynamicRequestContext $context): void;
}
