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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Maintenance;

use Pimcore\Messenger\MaintenanceTaskMessage;
use Pimcore\Model\Tool\TmpStore;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
final class Executor implements ExecutorInterface
{
    /**
     * @var array
     */
    private $tasks = [];

    /**
     * @var string
     */
    private $pidFileName;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LockFactory|null
     */
    private $lockFactory = null;

    public function __construct(
        string $pidFileName,
        LoggerInterface $logger,
        LockFactory $lockFactory,
        private MessageBusInterface $messengerBusPimcoreCore
    ) {
        $this->pidFileName = $pidFileName;
        $this->logger = $logger;
        $this->lockFactory = $lockFactory;
    }

    public function executeTask(string $name, bool $force = false)
    {
        if (!in_array($name, $this->getTaskNames(), true)) {
            throw new \Exception(sprintf('Task with name "%s" not found', $name));
        }

        $task = $this->tasks[$name];
        $lock = $this->lockFactory->createLock('maintenance-' . $name, 86400);

        if (!$lock->acquire() && !$force) {
            $this->logger->info('Skipped job with ID {id} because it already being executed', [
                'id' => $name,
            ]);

            return;
        }

        try {
            $this->logger->info('Starting job with ID {id}', [
                'id' => $name,
            ]);
            $task->execute();

            $this->logger->info('Finished job with ID {id}', [
                'id' => $name,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to execute job with ID {id}: {exception}', [
                'id' => $name,
                'exception' => $e,
            ]);
        }

        $lock->release();
    }

    /**
     * {@inheritdoc}
     */
    public function executeMaintenance(array $validJobs = [], array $excludedJobs = [], bool $force = false)
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

            $this->messengerBusPimcoreCore->dispatch(
                new MaintenanceTaskMessage($name, $force)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTaskNames()
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

    /**
     * {@inheritdoc}
     */
    public function setLastExecution()
    {
        TmpStore::set($this->pidFileName, time());
    }

    /**
     * {@inheritdoc}
     */
    public function getLastExecution()
    {
        $item = TmpStore::get($this->pidFileName);

        if ($item instanceof TmpStore && $date = $item->getData()) {
            return (int) $date;
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function registerTask($name, TaskInterface $task)
    {
        if (array_key_exists($name, $this->tasks)) {
            throw new \InvalidArgumentException(sprintf('Task with name %s has already been registered', $name));
        }

        $this->tasks[$name] = $task;
    }
}
