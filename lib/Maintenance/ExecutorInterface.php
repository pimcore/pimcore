<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Maintenance;

/**
 * @internal
 */
interface ExecutorInterface
{
    /**
     * Execute the Maintenance Task
     *
     * @param array $validJobs
     * @param array $excludedJobs
     * @param bool $force
     */
    public function executeMaintenance(array $validJobs = [], array $excludedJobs = [], bool $force = false);

    /**
     * @param string $name
     * @param TaskInterface $task
     */
    public function registerTask($name, TaskInterface $task);

    /**
     * @return array
     */
    public function getTaskNames();

    /**
     * @return int
     */
    public function getLastExecution();

    public function setLastExecution();
}
