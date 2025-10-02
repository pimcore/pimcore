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

namespace Pimcore\Console\Traits;

use Pimcore;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Webmozarts\Console\Parallelization\ErrorHandler\ErrorHandler;
use Webmozarts\Console\Parallelization\ParallelExecutorFactory;

trait Parallelization
{
    private ?LockInterface $lock = null;

    use ParallelizationBase;

    protected function getParallelExecutableFactory(
        callable $fetchItems,
        callable $runSingleCommand,
        callable $getItemName,
        string $commandName,
        InputDefinition $commandDefinition,
        ErrorHandler $errorHandler
    ): ParallelExecutorFactory {
        return ParallelExecutorFactory::create(...func_get_args())
            ->withRunBeforeFirstCommand($this->runBeforeFirstCommand(...))
            ->withRunAfterLastCommand($this->runAfterLastCommand(...))
            ->withRunAfterBatch($this->runAfterBatch(...));
    }

    protected function runBeforeFirstCommand(InputInterface $input, OutputInterface $output): void
    {
        if (!$this->lock()) {
            $this->writeError('The command is already running.');
            exit(1);
        }
    }

    protected function runAfterBatch(InputInterface $input, OutputInterface $output, array $items): void
    {
        if ($this->input->hasOption('processes') && (int)$this->input->getOption('processes') <= 1) {
            if ($output->isVeryVerbose()) {
                $output->writeln('Collect garbage.');
            }
            Pimcore::collectGarbage();
        }
    }

    protected function runAfterLastCommand(InputInterface $input, OutputInterface $output): void
    {
        $this->release(); //release the lock
    }

    protected function getItemName(?int $count): string
    {
        return $count === 1 ? 'item' : 'items';
    }

    /**
     * Locks a command.
     */
    private function lock(): bool
    {
        $this->lock = Pimcore::getContainer()->get(LockFactory::class)->createLock($this->getName(), 86400);

        if (!$this->lock->acquire()) {
            $this->lock = null;

            return false;
        }

        return true;
    }

    /**
     * Releases the command lock if there is one.
     */
    private function release(): void
    {
        if ($this->lock) {
            $this->lock->release();
            $this->lock = null;
        }
    }
}
