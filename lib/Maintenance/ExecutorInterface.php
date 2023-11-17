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

namespace Pimcore\Maintenance;

/**
 * @internal
 */
interface ExecutorInterface
{
    public function executeTask(string $name): void;

    /**
     * Execute the Maintenance Task
     *
     * @param string[] $validJobs
     * @param string[] $excludedJobs
     */
    public function executeMaintenance(array $validJobs = [], array $excludedJobs = []): void;

    public function registerTask(string $name, TaskInterface $task, ?string $messengerMessageClass = null): void;

    public function getTaskNames(): array;

    public function getLastExecution(): int;

    public function setLastExecution(): void;
}
