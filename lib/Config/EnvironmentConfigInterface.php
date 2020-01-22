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

namespace Pimcore\Config;

interface EnvironmentConfigInterface
{
    /**
     * Determines if the environment activates the kernel debug mode.
     *
     * @param string $environment
     *
     * @return bool
     */
    public function activatesKernelDebugMode(string $environment): bool;

    /**
     * Default environment to use if no environment is explicitely defined
     *
     * @return string
     */
    public function getDefaultEnvironment(): string;

    /**
     * Default environment to use if no environment is explicitely defined and Pimcore is in debug mode
     *
     * @deprecated
     *
     * @return string
     */
    public function getDefaultDebugModeEnvironment(): string;

    /**
     * Environments to handle in housekeeping maintenance job
     *
     * @return array
     */
    public function getProfilerHousekeepingEnvironments(): array;
}
