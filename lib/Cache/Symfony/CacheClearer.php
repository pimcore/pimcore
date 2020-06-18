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

use Pimcore\Process\PartsBuilder;
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
            'processTimeout' => 300,
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
            'no-warmup' => false,
            'no-optional-warmers' => false,
            'env' => $environment,
            'ansi' => false,
            'no-ansi' => false,
        ]);

        foreach (['no-warmup', 'no-optional-warmers', 'ansi', 'no-ansi'] as $option) {
            $resolver->setAllowedTypes($option, 'bool');
        }

        return $this->runCommand('cache:clear', [], $resolver->resolve($options));
    }

    public function warmup(string $environment, array $options = []): Process
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'no-optional-warmers' => false,
            'env' => $environment,
            'ansi' => false,
            'no-ansi' => false,
        ]);

        foreach (['no-optional-warmers', 'ansi', 'no-ansi'] as $option) {
            $resolver->setAllowedTypes($option, 'bool');
        }

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
        $arguments = array_merge([
            Console::getPhpCli(),
            'bin/console',
            $command,
        ], $arguments);

        $partsBuilder = new PartsBuilder($arguments, $options);
        $parts = $partsBuilder->getParts();

        $process = new Process($parts);
        $process
            ->setTimeout($this->processTimeout)
            ->setWorkingDirectory(PIMCORE_PROJECT_ROOT);

        return $process;
    }
}
