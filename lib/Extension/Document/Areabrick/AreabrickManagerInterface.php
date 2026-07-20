<?php

declare(strict_types = 1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Extension\Document\Areabrick;

interface AreabrickManagerInterface
{
    /**
     * Registers an areabrick on the manager
     *
     */
    public function register(string $id, AreabrickInterface $brick): void;

    /**
     * Registers a lazy loaded area brick service on the manager
     *
     */
    public function registerService(string $id, string $serviceId): void;

    /**
     * Fetches a brick by ID
     *
     *
     */
    public function getBrick(string $id): AreabrickInterface;

    /**
     * Lists all registered areabricks indexed by ID. Will implicitely load all bricks registered as service.
     *
     * @return AreabrickInterface[]
     */
    public function getBricks(): array;

    /**
     * Lists all registered areabrick IDs
     *
     */
    public function getBrickIds(): array;
}
