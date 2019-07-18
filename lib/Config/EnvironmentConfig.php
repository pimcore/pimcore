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

class EnvironmentConfig implements EnvironmentConfigInterface
{
    /**
     * Environments activating the kernel debug mode
     *
     * @var array
     */
    private $kernelDebugEnvironments = ['dev', 'test'];

    /**
     * The default environment to use used if no environment is explicitely
     * set and Pimcore is not in debug mode.
     *
     * @var string
     */
    private $defaultEnvironment = 'prod';

    /**
     * The default environment to use used if no environment is explicitely
     * set and Pimcore is in debug mode.
     *
     * @var string
     */
    private $defaultDebugModeEnvironment = 'dev';

    /**
     * Environments which will be handled by the profiler cleanup job
     *
     * @var array
     */
    private $profilerHousekeepingEnvironments = ['dev'];

    public function activatesKernelDebugMode(string $environment): bool
    {
        if (\Pimcore::inDebugMode()) {
            return true;
        }

        return in_array($environment, $this->kernelDebugEnvironments, true);
    }

    public function setKernelDebugEnvironments(array $kernelDebugEnvironments)
    {
        $this->kernelDebugEnvironments = $kernelDebugEnvironments;
    }

    public function getKernelDebugEnvironments(): array
    {
        return $this->kernelDebugEnvironments;
    }

    public function getDefaultEnvironment(): string
    {
        if (\Pimcore::inDebugMode()) {
            return $this->defaultDebugModeEnvironment;
        }

        return $this->defaultEnvironment;
    }

    public function setDefaultEnvironment(string $defaultEnvironment)
    {
        $this->defaultEnvironment = $defaultEnvironment;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultDebugModeEnvironment(): string
    {
        return $this->defaultDebugModeEnvironment;
    }

    /**
     * @deprecated
     *
     * @param string $defaultDebugModeEnvironment
     */
    public function setDefaultDebugModeEnvironment(string $defaultDebugModeEnvironment)
    {
        $this->defaultDebugModeEnvironment = $defaultDebugModeEnvironment;
    }

    public function setProfilerHousekeepingEnvironments(array $profilerHousekeepingEnvironments)
    {
        $this->profilerHousekeepingEnvironments = $profilerHousekeepingEnvironments;
    }

    public function getProfilerHousekeepingEnvironments(): array
    {
        return $this->profilerHousekeepingEnvironments;
    }
}
