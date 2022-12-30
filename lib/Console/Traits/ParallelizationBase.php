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

namespace Pimcore\Console\Traits;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozarts\Console\Parallelization\Input\ParallelizationInput;

if (trait_exists('\Webmozarts\Console\Parallelization\Parallelization')) {
    trait ParallelizationBase
    {
        use \Webmozarts\Console\Parallelization\Parallelization
        {
            \Webmozarts\Console\Parallelization\Parallelization::execute as parentExecute;
        }

        protected function configureCommand(Command $command): void
        {
            ParallelizationInput::configureCommand($command);
        }
    }
} else {
    trait ParallelizationBase
    {
        /**
         * {@inheritdoc}
         */
        public function execute(InputInterface $input, OutputInterface $output): int
        {
            $this->runBeforeFirstCommand($input, $output);

            $items = $this->fetchItems($input, $output);

            //Method executed before executing all the items
            if (method_exists($this, 'runBeforeBatch')) {
                $this->runBeforeBatch($input, $output, $items);
            }

            foreach ($items as $item) {
                $this->runSingleCommand(trim($item), $input, $output);
            }

            //Method executed after executing all the items
            if (method_exists($this, 'runAfterBatch')) {
                $this->runAfterBatch($input, $output, $items);
            }

            $this->runAfterLastCommand($input, $output);

            return 0;
        }

        protected function configureCommand(Command $command): void
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
                    '1'
                )
                ->addOption(
                    'main-process',
                    'm',
                    InputOption::VALUE_NONE,
                    'To execute the processing in the main process (no child processes will be spawned).',
                )
                ->addOption(
                    'child',
                    null,
                    InputOption::VALUE_NONE,
                    'Set on child processes. For internal use only.'
                )
                ->addOption(
                    'batch-size',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Sets the number of items to process per child process or in a batch',
                )
                ->addOption(
                    'segment-size',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Set the segment size.',
                );
        }
    }
}
