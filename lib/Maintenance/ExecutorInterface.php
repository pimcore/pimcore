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
