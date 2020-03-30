<?php

declare(strict_types = 1);

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

namespace Pimcore\Extension\Document\Areabrick;

interface AreabrickManagerInterface
{
    /**
     * Registers an areabrick on the manager
     *
     * @param string $id
     * @param AreabrickInterface $brick
     */
    public function register(string $id, AreabrickInterface $brick);

    /**
     * Registers a lazy loaded area brick service on the manager
     *
     * @param string $id
     * @param string $serviceId
     */
    public function registerService(string $id, string $serviceId);

    /**
     * Fetches a brick by ID
     *
     * @param string $id
     *
     * @return AreabrickInterface
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
     * @return array
     */
    public function getBrickIds(): array;

    /**
     * Enables an areabrick
     *
     * @param string $id
     */
    public function enable(string $id);

    /**
     * Disables an areabrick
     *
     * @param string $id
     */
    public function disable(string $id);

    /**
     * Determines if an areabrick is enabled. Bricks are enabled by default an can be switched off by setting
     * the state explicitely to false in the extension config.
     *
     * @param string $id
     *
     * @return bool
     */
    public function isEnabled(string $id): bool;

    /**
     * Enables/disables an areabrick
     *
     * @param string $id
     * @param bool $state
     */
    public function setState(string $id, bool $state);
}
