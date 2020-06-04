<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Console\Traits;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webmozart\Assert\Assert;
use Webmozarts\Console\Parallelization\Parallelization as WebmozartParallelization;
use Webmozarts\Console\Parallelization\ProcessLauncher;

trait Parallelization
{
    use LockableTrait;
    
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

    protected function getItemName(int $count): string
    {
        return $count <= 1 ? 'item' : 'items';
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface|null
     */
    protected function getContainer()
    {
        return \Pimcore::getKernel()->getContainer();
    }


    // ----------- temporary fix for quoting options ----------------------


    protected function needsQuote($value) {
        return 0 < preg_match('/[\s \\\\ \' " & | < > = ! @]/x', $value);
    }

    protected function quoteOptionValue($value) {

        if($this->needsQuote($value)) {
            return sprintf('"%s"', str_replace('"', '\"', $value));
        }

        return $value;
    }


    /**
     * @param string[] $blackListParams
     * @return string[]
     */
    private function serializeInputOptions(InputInterface $input, array $blackListParams) : array {
        $options = array_diff_key(
            array_filter($input->getOptions()),
            array_fill_keys($blackListParams, '')
        );

        $preparedOptionList = [];
        foreach ($options as $name => $value) {
            $definition = $this->getDefinition();
            $option = $definition->getOption($name);

            $optionString  = "";
            if (!$option->acceptValue()) {
                $optionString .= ' --' . $name;
            } elseif ($option->isArray()) {
                foreach ($value as $arrayValue) {
                    $optionString .= ' --'.$name.'='.$this->quoteOptionValue($arrayValue);
                }
            } else {
                $optionString .= ' --'.$name.'='.$this->quoteOptionValue($value);
            }

            $preparedOptionList[] = $optionString;
        }
        return $preparedOptionList;
    }


    /**
     * Executes the master process.
     *
     * The master process spawns as many child processes as set in the
     * "--processes" option. Each of the child processes receives a segment of
     * items of the processed data set and terminates. As long as there is data
     * left to process, new child processes are spawned automatically.
     */
    protected function executeMasterProcess(InputInterface $input, OutputInterface $output): void
    {
        $this->runBeforeFirstCommand($input, $output);

        $numberOfProcessesDefined = null !== $input->getOption('processes');
        $numberOfProcesses = $numberOfProcessesDefined ? (int) $input->getOption('processes') : 1;
        $hasItem = (bool) $input->getArgument('item');
        $items = $hasItem ? [$input->getArgument('item')] : $this->fetchItems($input);
        $count = count($items);
        $segmentSize = 1 === $numberOfProcesses && !$numberOfProcessesDefined ? $count : $this->getSegmentSize();
        $batchSize = $this->getBatchSize();
        $rounds = 1 === $numberOfProcesses ? 1 : ceil($count * 1.0 / $segmentSize);
        $batches = ceil($segmentSize * 1.0 / $batchSize) * $rounds;

        Assert::greaterThan(
            $numberOfProcesses,
            0,
            sprintf(
                'Requires at least one process. Got "%s"',
                $input->getOption('processes')
            )
        );

        if (!$hasItem && 1 !== $numberOfProcesses) {
            // Shouldn't check this when only one item has been specified or
            // when no child processes is used
            Assert::greaterThanEq(
                $segmentSize,
                $batchSize,
                sprintf(
                    'The segment size should always be greater or equal to '
                    .'the batch size. Got respectively "%d" and "%d"',
                    $segmentSize,
                    $batchSize
                )
            );
        }

        $output->writeln(sprintf(
            'Processing %d %s in segments of %d, batches of %d, %d %s, %d %s in %d %s',
            $count,
            $this->getItemName($count),
            $segmentSize,
            $batchSize,
            $rounds,
            1 === $rounds ? 'round' : 'rounds',
            $batches,
            1 === $batches ? 'batch' : 'batches',
            $numberOfProcesses,
            1 === $numberOfProcesses ? 'process' : 'processes'
        ));
        $output->writeln('');

        $progressBar = new ProgressBar($output, $count);
        $progressBar->setFormat('debug');
        $progressBar->start();

        if ($count <= $segmentSize || (1 === $numberOfProcesses && !$numberOfProcessesDefined)) {
            // Run in the master process

            $itemsChunks = array_chunk(
                $items,
                $this->getBatchSize(),
                false
            );

            foreach ($itemsChunks as $items) {
                $this->runBeforeBatch($input, $output, $items);

                foreach ($items as $item) {
                    $this->runTolerantSingleCommand((string) $item, $input, $output);

                    $progressBar->advance();
                }

                $this->runAfterBatch($input, $output, $items);
            }
        } else {
            // Distribute if we have multiple segments
            $consolePath = realpath(getcwd().'/bin/console');
            Assert::fileExists(
                $consolePath,
                sprintf('The bin/console file could not be found at %s', getcwd()))
            ;

            $commandTemplate = implode(
                ' ',
                array_merge(
                    array_filter([
                        self::detectPhpExecutable(),
                        $consolePath,
                        $this->getName(),
                        implode(' ', array_slice($input->getArguments(), 1)),
                        '--child',
                    ]),
                    $this->serializeInputOptions($input, ['child', 'processes'])
                )
            );
            $terminalWidth = (new Terminal())->getWidth();

            $processLauncher = new ProcessLauncher(
                $commandTemplate,
                self::getWorkingDirectory($this->getContainer()),
                self::getEnvironmentVariables($this->getContainer()),
                $numberOfProcesses,
                $segmentSize,
                $this->getContainer()->get('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                function (string $type, string $buffer) use ($progressBar, $output, $terminalWidth) {
                    $this->processChildOutput($buffer, $progressBar, $output, $terminalWidth);
                }
            );

            $processLauncher->run($items);
        }

        $progressBar->finish();

        $output->writeln('');
        $output->writeln('');
        $output->writeln(sprintf(
            'Processed %d %s.',
            $count,
            $this->getItemName($count)
        ));

        $this->runAfterLastCommand($input, $output);
    }







}
