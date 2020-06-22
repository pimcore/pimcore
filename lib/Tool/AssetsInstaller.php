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

namespace Pimcore\Tool;

use Pimcore\Process\PartsBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Runs the assets:install command with the settings configured in composer.json
 *
 * @package Pimcore\Tool
 */
class AssetsInstaller
{
    /**
     * @var \Closure
     */
    private $runCallback;

    /**
     * @var string
     */
    private $composerJsonSetting;

    /**
     * Runs this assets:install command
     *
     * @param array $options
     *
     * @return Process
     */
    public function install(array $options = []): Process
    {
        $process = $this->buildProcess($options);
        $process->setTimeout(240);
        $process->run($this->runCallback);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }

    /**
     * Builds the process instance
     *
     * @param array $options
     *
     * @return Process
     */
    public function buildProcess(array $options = []): Process
    {
        $arguments = [
            Console::getPhpCli(),
            PIMCORE_PROJECT_ROOT . '/bin/console',
            'assets:install',
            'web',
        ];

        $options = $this->resolveOptions($options);

        $partsBuilder = new PartsBuilder($arguments, $options);
        $parts = $partsBuilder->getParts();

        $process = new Process($parts);
        $process->setWorkingDirectory(PIMCORE_PROJECT_ROOT);

        return $process;
    }

    /**
     * Takes a set of options as defined in configureOptions and validates and merges them
     * with values from composer.json
     *
     * @param array $options
     *
     * @return array
     */
    public function resolveOptions(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        return $resolver->resolve($options);
    }

    /**
     * @param \Closure $runCallback
     */
    public function setRunCallback(\Closure $runCallback = null)
    {
        $this->runCallback = $runCallback;
    }

    private function configureOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'symlink' => true,
            'relative' => true,
            'env' => false,
            'ansi' => false,
            'no-ansi' => false,
        ];

        $composerJsonSetting = $this->readComposerJsonSetting();
        if (null !== $composerJsonSetting) {
            if ('symlink' === $composerJsonSetting) {
                $defaults = array_merge([
                    'symlink' => true,
                    'relative' => false,
                ], $defaults);
            } elseif ('relative' === $composerJsonSetting) {
                $defaults = array_merge([
                    'symlink' => true,
                    'relative' => true,
                ], $defaults);
            }
        }

        $resolver->setDefaults($defaults);

        foreach (['symlink', 'relative', 'ansi', 'no-ansi'] as $option) {
            $resolver->setAllowedTypes($option, 'bool');
        }
    }

    /**
     * @return string|null
     */
    private function readComposerJsonSetting()
    {
        if (null !== $this->composerJsonSetting) {
            return $this->composerJsonSetting;
        }

        $file = PIMCORE_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'composer.json';
        if (file_exists($file)) {
            $contents = file_get_contents($file);

            if (!empty($contents)) {
                try {
                    $json = json_decode($contents, true);

                    if (JSON_ERROR_NONE === json_last_error() && $json && isset($json['extra']) && isset($json['extra']['symfony-assets-install'])) {
                        $this->composerJsonSetting = $json['extra']['symfony-assets-install'];
                    }
                } catch (\Exception $e) {
                    // noop
                }
            }
        }

        return $this->composerJsonSetting;
    }
}
