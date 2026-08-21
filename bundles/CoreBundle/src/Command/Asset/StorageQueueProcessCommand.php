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

namespace Pimcore\Bundle\CoreBundle\Command\Asset;

use Pimcore\Asset\StorageQueue\StorageOperationQueueProcessor;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:assets:storage-queue:process',
    description: 'Apply pending asset storage operations (folder moves/deletes deferred by the storage operation queue)'
)]
final class StorageQueueProcessCommand extends AbstractCommand
{
    private const LOCK_NAME = 'asset_storage_operation_queue_process';

    public function __construct(
        private readonly LockFactory $lockFactory,
        private readonly ?StorageOperationQueueProcessor $processor = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Process only the given queue row')
            ->addOption('max-runtime', null, InputOption::VALUE_REQUIRED, 'Stop cleanly after this many seconds; unfinished rows stay queued');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->processor === null) {
            $this->writeError('The storage operation queue is disabled (pimcore.assets.storage_operation_queue.enabled) - nothing can be processed.');

            return self::FAILURE;
        }

        $lock = $this->lockFactory->createLock(self::LOCK_NAME, 86400);
        if (!$lock->acquire()) {
            $output->writeln('<comment>Another storage-queue:process run is already running - skipping.</comment>');

            return self::SUCCESS;
        }

        try {
            $id = $input->getOption('id') !== null ? (int) $input->getOption('id') : null;
            $maxRuntime = $input->getOption('max-runtime') !== null ? (int) $input->getOption('max-runtime') : null;

            $result = $this->processor->process($id, $maxRuntime);

            $output->writeln(sprintf(
                '%d processed, %d failed, %d pending%s',
                $result->getProcessedRows(),
                $result->getFailedRows(),
                $result->getPendingRows(),
                $result->isTimedOut() ? ' (stopped at max-runtime)' : ''
            ));
            if ($id !== null && $result->getProcessedRows() === 0 && $result->getFailedRows() === 0) {
                $output->writeln(sprintf('<comment>No queue row found with id %d.</comment>', $id));
            }
            foreach ($result->getErrors() as $error) {
                $this->writeError($error);
            }

            return $result->getFailedRows() > 0 ? self::FAILURE : self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
