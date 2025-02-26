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

use Exception;
use InvalidArgumentException;
use Pimcore\Messenger\MaintenanceTaskMessage;
use Pimcore\Model\Tool\TmpStore;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
final class Executor implements ExecutorInterface
{
    private array $tasks = [];

    private string $pidFileName;

    private LoggerInterface $logger;

    public function __construct(
        string $pidFileName,
        LoggerInterface $logger,
        private MessageBusInterface $messengerBusPimcoreCore
    ) {
        $this->pidFileName = $pidFileName;
        $this->logger = $logger;
    }

    public function executeTask(string $name): void
    {
        if (!in_array($name, $this->getTaskNames(), true)) {
            throw new Exception(sprintf('Task with name "%s" not found', $name));
        }

        $task = $this->tasks[$name]['taskClass'];

        try {
            $this->logger->info('Starting job with ID {id}', [
                'id' => $name,
            ]);
            $task->execute();

            $this->logger->info('Finished job with ID {id}', [
                'id' => $name,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to execute job with ID {id}: {exception}', [
                'id' => $name,
                'exception' => $e,
            ]);
        }
    }

    public function executeMaintenance(array $validJobs = [], array $excludedJobs = []): void
    {
        $this->setLastExecution();

        foreach ($this->tasks as $name => $task) {
            if (count($validJobs) > 0 && !in_array($name, $validJobs, true)) {
                $this->logger->info('Skipped job with ID {id} because it is not in the valid jobs', [
                    'id' => $name,
                ]);

                continue;
            }

            if (count($excludedJobs) > 0 && in_array($name, $excludedJobs, true)) {
                $this->logger->info('Skipped job with ID {id} because it has been excluded', [
                    'id' => $name,
                ]);

                continue;
            }

            $className = $task['messengerMessageClass'] ?? MaintenanceTaskMessage::class;
            $this->messengerBusPimcoreCore->dispatch(
                new $className($name)
            );
        }
    }

    public function getTaskNames(): array
    {
        return array_keys($this->tasks);
    }

    /**
     * @return TaskInterface[]
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    public function setLastExecution(): void
    {
        TmpStore::set($this->pidFileName, time());
    }

    public function getLastExecution(): int
    {
        $item = TmpStore::get($this->pidFileName);

        if ($item instanceof TmpStore && $date = $item->getData()) {
            return (int) $date;
        }

        return 0;
    }

    public function registerTask(string $name, TaskInterface $task, ?string $messengerMessageClass = null): void
    {
        if (array_key_exists($name, $this->tasks)) {
            throw new InvalidArgumentException(sprintf('Task with name %s has already been registered', $name));
        }

        $this->tasks[$name] = ['taskClass' => $task, 'messengerMessageClass' => $messengerMessageClass];
    }
}
