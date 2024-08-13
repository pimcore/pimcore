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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozarts\Console\Parallelization\Input\ParallelizationInput;

if (trait_exists('\Webmozarts\Console\Parallelization\Parallelization')) {
    trait ParallelizationBase
    {
        use \Webmozarts\Console\Parallelization\Parallelization
        {
            \Webmozarts\Console\Parallelization\Parallelization::execute as parentExecute;
        }

        protected static function configureCommand(Command $command): void
        {
            ParallelizationInput::configureCommand($command);
        }
    }
} else {
    trait ParallelizationBase
    {
        public function execute(InputInterface $input, OutputInterface $output): int
        {
            $this->runBeforeFirstCommand($input, $output);

            $items = $this->fetchItems($input, $output);

            //Method executed before executing all the items
            if (method_exists($this, 'runBeforeBatch')) {
                $this->runBeforeBatch($input, $output, $items);
            }

            foreach ($items as $item) {
                $this->runSingleCommand(trim((string)$item), $input, $output);
            }

            //Method executed after executing all the items
            if (method_exists($this, 'runAfterBatch')) {
                $this->runAfterBatch($input, $output, $items);
            }

            $this->runAfterLastCommand($input, $output);

            return 0;
        }

        protected static function configureCommand(Command $command): void
        {
            // nothing to do here since parallelization is disabled
        }
    }
}
