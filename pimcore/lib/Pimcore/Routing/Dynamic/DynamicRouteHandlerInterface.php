<?php

declare(strict_types=1);

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

namespace Pimcore\Routing\Dynamic;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

interface DynamicRouteHandlerInterface
{
    /**
     * Find the route using the provided route name.
     *
     * @param string $name The route name to fetch
     *
     * @return Route
     *
     * @throws RouteNotFoundException If there is no route with that name in
     *                                this repository
     */
    public function getRouteByName(string $name);

    /**
     * Add matching routes to the route collection
     *
     * @param RouteCollection $collection
     * @param DynamicRequestContext $context
     */
    public function matchRequest(RouteCollection $collection, DynamicRequestContext $context);
}
