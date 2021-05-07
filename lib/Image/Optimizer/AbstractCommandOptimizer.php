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

namespace Pimcore\Image\Optimizer;

use Pimcore\Exception\ImageOptimizationFailedException;
use Pimcore\Tool\Console;
use Symfony\Component\Process\Process;

abstract class AbstractCommandOptimizer implements OptimizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function optimizeImage(string $input, string $output): string
    {
        $executable = $this->getExecutable();

        if ($executable) {
            $command = $this->getCommandArray($executable, $input, $output);

            //@TODO Remove in Pimcore 10
            if (empty($command)) {
                $command = $this->getCommand($executable, $input, $output);
            }

            Console::addLowProcessPriority($command);
            $process = new Process($command);
            $process->run();

            if (file_exists($output) && filesize($output) > 0) {
                return $output;
            }

            throw new ImageOptimizationFailedException(sprintf('Could not create optimized image with command "%s"',
                $command));
        }

        throw new ImageOptimizationFailedException('Could not find executable');
    }

    /**
     * @return string
     */
    abstract protected function getExecutable(): string;

    /**
     * @deprecated use getCommandArray() instead.
     *
     * @param string $executable
     * @param string $input
     * @param string $output
     *
     * @return string
     */
    abstract protected function getCommand(string $executable, string $input, string $output): string;

    /**
     * @TODO make abstract in Pimcore 10
     *
     * @param string $executable
     * @param string $input
     * @param string $output
     *
     * @return array
     */
    protected function getCommandArray(string $executable, string $input, string $output): array
    {
        return [];
    }
}
