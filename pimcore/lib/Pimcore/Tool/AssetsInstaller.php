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

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Serializer\Serializer;

/**
 * Runs the assets:install command with the settings configured in composer.json
 *
 * @package Pimcore\Tool
 */
class AssetsInstaller
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var string
     */
    private $composerJsonSetting;

    /**
     * @param KernelInterface $kernel
     * @param Serializer $serializer
     */
    public function __construct(KernelInterface $kernel, Serializer $serializer)
    {
        $this->kernel     = $kernel;
        $this->serializer = $serializer;
    }

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
        $process->run();

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
        $options = $this->resolveOptions($options);

        $builder = new ProcessBuilder([
            'assets:install',
            'web',
            '--env=' . $this->kernel->getEnvironment()
        ]);

        $builder
            ->setWorkingDirectory(PIMCORE_PROJECT_ROOT)
            ->setPrefix('bin/console');

        if (!$options['ansi']) {
            $builder->add('--no-ansi');
        } else {
            $builder->add('--ansi');
        }

        if ($options['symlink']) {
            $builder->add('--symlink');
        }

        if ($options['relative']) {
            $builder->add('--relative');
        }

        return $builder->getProcess();
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

    private function configureOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'ansi'     => false,
            'symlink'  => true,
            'relative' => true
        ];

        $composerJsonSetting = $this->readComposerJsonSetting();
        if (null !== $composerJsonSetting) {
            if ('symlink' === $composerJsonSetting) {
                $defaults = array_merge([
                    'symlink'  => true,
                    'relative' => false
                ], $defaults);
            } elseif ('relative' === $composerJsonSetting) {
                $defaults = array_merge([
                    'symlink'  => true,
                    'relative' => true
                ], $defaults);
            }
        }

        $resolver->setDefaults($defaults);
    }

    /**
     * @return string|null
     */
    private function readComposerJsonSetting()
    {
        if (null !== $this->composerJsonSetting) {
            return $this->composerJsonSetting;
        }

        $json = [];
        $file = PIMCORE_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'composer.json';
        if (file_exists($file)) {
            $contents = file_get_contents($file);

            if (!empty($contents)) {
                try {
                    $json = $this->serializer->decode($json, 'json', ['json_decode_associative' => true]);

                    if ($json && isset($json['extra']) && isset($json['extra']['symfony-assets-install'])) {
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
