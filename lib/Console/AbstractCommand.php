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

namespace Pimcore\Console;

use Pimcore\Console\Style\PimcoreStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * Base command class setting up some defaults (e.g. the VarDumper component).
 *
 * @method Application getApplication()
 */
abstract class AbstractCommand extends Command
{
    protected PimcoreStyle $io;

    protected InputInterface $input;

    protected OutputInterface $output;

    private ?CliDumper $cliDumper = null;

    private ?VarCloner $varCloner = null;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->io = new PimcoreStyle($input, $output);
        $this->input = $input;
        $this->output = $output;
    }

    protected function dump(mixed $data): void
    {
        $this->doDump($data);
    }

    protected function dumpVerbose(mixed $data): void
    {
        if ($this->output->isVerbose()) {
            $this->doDump($data);
        }
    }

    private function doDump(mixed $data): void
    {
        if (null === $this->cliDumper) {
            $this->cliDumper = new CliDumper();
            $output = $this->output instanceof StreamOutput ? $this->output->getStream() : function ($line, $depth, $indentPad) {
                if (-1 !== $depth) {
                    $this->output->writeln(str_repeat($indentPad, $depth) . $line);
                }
            };
            $this->cliDumper->setOutput($output);
            $this->varCloner = new VarCloner();
        }

        $this->cliDumper->dump($this->varCloner->cloneVar($data));
    }

    protected function writeError(string $message): void
    {
        $this->output->writeln(sprintf('<error>ERROR: %s</error>', $message));
    }

    protected function writeInfo(string $message): void
    {
        $this->output->writeln(sprintf('<info>INFO: %s</info>', $message));
    }

    protected function writeComment(string $message): void
    {
        $this->output->writeln(sprintf('<comment>COMMENT: %s</comment>', $message));
    }

    protected function writeQuestion(string $message): void
    {
        $this->output->writeln(sprintf('<question>QUESTION: %s</question>', $message));
    }
}
