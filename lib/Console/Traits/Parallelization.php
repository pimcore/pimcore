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

namespace Pimcore\Console\Traits;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Factory as LockFactory;
use Symfony\Component\Lock\LockInterface;
use Webmozarts\Console\Parallelization\Parallelization as WebmozartParallelization;

trait Parallelization
{
    /** @var LockInterface|null */
    private $lock;

    use WebmozartParallelization
    {
        WebmozartParallelization::configureParallelization as parentConfigureParallelization;
    }

    protected static function configureParallelization(Command $command): void
    {
        $command
            ->addArgument(
                'item',
                InputArgument::OPTIONAL,
                'The item to process. Can be used in commands where simple IDs are processed. Otherwise it is for internal use.'
            )
            ->addOption(
                'processes',
                null,
                //'p', avoid collisions with already existing Pimcore command options
                InputOption::VALUE_OPTIONAL,
                'The number of parallel processes to run',
                1
            )
            ->addOption(
                'child',
                null,
                InputOption::VALUE_NONE,
                'Set on child processes. For internal use only.'
            )
        ;
    }

    /**
     * Default behavior in commands: only allow one command of a type at the same time.
     */
    protected function runBeforeFirstCommand(InputInterface $input, OutputInterface $output): void
    {
        if (!$this->lock()) {
            $this->writeError('The command is already running.');
            exit(1);
        }
    }

    /**
     * Default behavior in commands: clean up garbage after each batch run, if there is only
     * one master process in place.
     *
     * @param array $items
     */
    protected function runAfterBatch(InputInterface $input, OutputInterface $output, array $items): void
    {
        if ((int)$this->input->getOption('processes') <= 1) {
            if ($output->isVeryVerbose()) {
                $output->writeln('Collect garbage.');
            }
            \Pimcore::collectGarbage();
        }
    }

    /**
     * Default behavior in commands: release lock on termination.
     */
    protected function runAfterLastCommand(InputInterface $input, OutputInterface $output): void
    {
        $this->release(); //release the lock
    }

    /**
     * @inheritDoc
     */
    protected function getItemName(int $count): string
    {
        return $count <= 1 ? 'item' : 'items';
    }

    /**
     * @inheritDoc
     */
    protected function getContainer()
    {
        return \Pimcore::getKernel()->getContainer();
    }

    /**
     * @inheritDoc
     */
    public function getConsolePath(): string
    {
        return PIMCORE_PROJECT_ROOT . '/bin/console';
    }

    /**
     * Locks a command.
     *
     * @param string|null $name
     * @param bool|null $blocking
     *
     * @return bool
     */
    private function lock($name = null, $blocking = false)
    {
        $this->lock = \Pimcore::getContainer()->get(LockFactory::class)->createLock($name ?: $this->getName());

        if (!$this->lock->acquire($blocking)) {
            $this->lock = null;

            return false;
        }

        return true;
    }

    /**
     * Releases the command lock if there is one.
     */
    private function release()
    {
        if ($this->lock) {
            $this->lock->release();
            $this->lock = null;
        }
    }
}
