<?php

declare(strict_types=1);

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

namespace Pimcore\Cache\Symfony;

use Pimcore\Tool\Console;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CacheClearer
{
    /**
     * @var int
     */
    private $processTimeout;

    /**
     * @var \Closure
     */
    private $runCallback;

    public function __construct(array $options = [])
    {
        $this->resolveOptions($options);
    }

    private function resolveOptions(array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'processTimeout' => 300
        ]);

        $resolver->setAllowedTypes('processTimeout', 'int');
        $resolver->setRequired('processTimeout');

        $options = $resolver->resolve($options);

        $this->processTimeout = $options['processTimeout'];
    }

    public function clear(string $environment, array $options = []): Process
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'no-warmup'           => false,
            'no-optional-warmers' => false,
            'ansi'                => false,
            'env'                 => $environment,
        ]);

        return $this->runCommand('cache:clear', [], $resolver->resolve($options));
    }

    public function warmup(string $environment, array $options = []): Process
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'no-optional-warmers' => false,
            'ansi'                => false,
            'env'                 => $environment,
        ]);

        return $this->runCommand('cache:warmup', [], $resolver->resolve($options));
    }

    /**
     * @param \Closure $runCallback
     */
    public function setRunCallback(\Closure $runCallback = null)
    {
        $this->runCallback = $runCallback;
    }

    private function runCommand(string $command, array $arguments = [], array $options = [])
    {
        $process = $this->buildProcess($command, $arguments, $options);
        $process->run($this->runCallback);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }

    private function buildProcess(string $command, array $arguments = [], array $options = []): Process
    {
        $parts = $this->buildProcessParts($arguments, $options);
        $parts = array_merge([
            Console::getPhpCli(),
            'bin/console',
            $command
        ], $parts);

        $process = new Process($parts);
        $process
            ->setTimeout($this->processTimeout)
            ->setWorkingDirectory(PIMCORE_PROJECT_ROOT);

        return $process;
    }

    private function buildProcessParts(array $arguments = [], array $options = []): array
    {
        $parts = [];
        foreach ($options as $optionKey => $option) {
            // do not set option if it is false
            if (is_bool($option) && !$option) {
                continue;
            }

            $part = '';
            if (1 === strlen($optionKey)) {
                $part = '-' . $optionKey;
            } else {
                $part = '--' . $optionKey;
            }

            if (!is_bool($option) && $option) {
                $part .= '=' . $option;
            }

            $parts[] = $part;
        }

        foreach ($arguments as $argument) {
            $parts[] = $argument;
        }

        return $parts;
    }
}
