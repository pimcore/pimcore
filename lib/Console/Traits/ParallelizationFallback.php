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

use Pimcore\Console\Exception\CommandAlreadyRunningException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sequential run used by ParallelizationBase when webmozarts/console-parallelization is not installed.
 *
 * @internal
 */
trait ParallelizationFallback
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->runBeforeFirstCommand($input, $output);
        } catch (CommandAlreadyRunningException) {
            return Command::SUCCESS;
        }

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
}
