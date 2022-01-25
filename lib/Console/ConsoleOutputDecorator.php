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

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Implements a ConsoleOutput with configurable output. Useful when needing to catch both output
 * and error output in a buffered output.
 *
 * @internal
 */
class ConsoleOutputDecorator implements OutputInterface, ConsoleOutputInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var OutputInterface
     */
    private $errorOutput;

    public function __construct(OutputInterface $output, OutputInterface $errorOutput)
    {
        $this->output = $output;
        $this->errorOutput = $errorOutput;
    }

    public function write($messages, $newline = false, $options = 0)
    {
        $this->output->write($messages, $newline, $options);
    }

    public function writeln($messages, $options = 0)
    {
        $this->output->writeln($messages, $options);
    }

    public function setVerbosity($level)
    {
        $this->output->setVerbosity($level);
    }

    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    public function setDecorated($decorated)
    {
        $this->output->setDecorated($decorated);
    }

    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter)
    {
        $this->output->setFormatter($formatter);
    }

    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->errorOutput;
    }

    public function setErrorOutput(OutputInterface $error)
    {
        $this->errorOutput = $error;
    }

    public function section(): ConsoleSectionOutput
    {
        $sections = [];
        $stream = @fopen('php://stdout', 'w') ?: fopen('php://output', 'w');

        return new ConsoleSectionOutput($stream, $sections, $this->getVerbosity(), $this->isDecorated(), $this->getFormatter());
    }
}
