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

use Pimcore\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Trait Timeout
 * Use this trait to implement a simple timeout mechanism in your command or service.
 */
trait Timeout
{
    /** @var int */
    private $timeout = -1;

    private $startTimeCurrentStep = null;

    private $startTime = null;

    /**
     * Add timeout option to command.
     *
     * @param Command $command
     */
    protected static function configureTimeout(Command $command): void
    {
        $command->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'Max time for the command to run in minutes.');
    }

    /**
     * Init the timeout. Should be called in the beginning of a command or process.
     *
     * @param InputInterface $input
     */
    protected function initTimeout(InputInterface $input)
    {
        $timeout = (int)$input->getOption('timeout');
        $timeout = $timeout > 0 ? $timeout : -1;
        $this->initTimeoutInMinutes($timeout);
    }

    /**
     * Init the timeout. Should be called in the beginning of a new batch process, if it is not a command.
     *
     * @param int $minutes the timeout in minutes.
     */
    protected function initTimeoutInMinutes(int $minutes)
    {
        $this->setTimeout($minutes);
        $this->startTime = time();
        $this->startTimeCurrentStep = null;
    }

    /**
     *
     * Handle timeout should be called periodically in your command or process,
     * after processing an item.
     *
     * @param \Closure|null $abortClosure use to implement a custom error handling that is executed when the timeout happens.
     *
     * @throws \Exception is thrown in the default implementation when the timeout happens
     */
    protected function handleTimeout(?\Closure $abortClosure = null)
    {
        $oldStartTime = $this->startTimeCurrentStep;
        $this->startTimeCurrentStep = time();
        $timeSinceStartMinutes = max(0, floor(($this->startTimeCurrentStep - $this->startTime) / 60));
        if ($this->timeout > 0) {
            if ($this->timeout <= $timeSinceStartMinutes) {
                $abortMessage = sprintf('Timeout "%d minutes" of processor has been reach. Aborted (this is ok).', $this->timeout);
                if ($abortClosure) {
                    $abortClosure($abortMessage);
                } else {
                    //default implementation: throw exeption
                    throw new \Exception($abortMessage);
                }
            } elseif (is_null($oldStartTime) || date('i', $oldStartTime) != date('i', $this->startTimeCurrentStep)) {
                Logger::debug('Timeout enabled. Still needs '.($this->timeout - $timeSinceStartMinutes).' minutes in order to complete.');
            }
        }
    }

    /**
     * Get the timeout in minutes. If <= 0 then no timeout is given.
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Set the timeout in minutes. If not set, no timeout happens
     *
     * @param int $timeout
     *
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Get the start time of the current step in seconds (unixtime).
     *
     * @return null
     */
    public function getStartTimeCurrentStep(): ?int
    {
        return $this->startTimeCurrentStep;
    }

    /**
     * Get the start time of the current (overall) process in seconds (unixtime).
     *
     * @return int|null
     */
    public function getStartTime(): ?int
    {
        return $this->startTime;
    }
}
