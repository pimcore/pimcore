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

namespace Pimcore\Bundle\InstallBundle;

use Exception;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @internal
 */
class InstallerKernel extends Kernel
{
    use MicroKernelTrait;

    private string $projectRoot;

    public function __construct(string $projectRoot, string $environment, bool $debug)
    {
        $this->projectRoot = $projectRoot;

        parent::__construct($environment, $debug);
    }

    public function getProjectDir(): string
    {
        return $this->projectRoot;
    }

    public function getLogDir(): string
    {
        return $this->projectRoot . '/var/installer/log';
    }

    public function getCacheDir(): string
    {
        return $this->projectRoot . '/var/installer/cache';
    }

    public function getBuildDir(): string
    {
        return $this->projectRoot . '/var/installer/build';
    }

    public function registerBundles(): array
    {
        $bundles = [
            new FrameworkBundle(),
            new MonologBundle(),
            new PimcoreInstallBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'])) {
            $bundles[] = new DebugBundle();
        }

        return $bundles;
    }

    /**
     * @throws Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        // Register the synthetic "kernel" service. This is normally done by
        // MicroKernelTrait::registerContainerConfiguration() and is required so
        // that other services may depend on the kernel via DI.
        $loader->load(function (ContainerBuilder $container): void {
            if (!$container->hasDefinition('kernel')) {
                $container->register('kernel', static::class)
                    ->addTag('controller.service_arguments')
                    ->setAutoconfigured(true)
                    ->setSynthetic(true)
                    ->setPublic(true);
            }

            $container->setParameter('secret', uniqid('installer-', true));
        });

        $loader->load('@PimcoreInstallBundle/config/config.yaml');

        foreach (['php', 'yaml', 'yml', 'xml'] as $extension) {
            $file = sprintf('%s/config/installer.%s', $this->getProjectDir(), $extension);

            if (file_exists($file)) {
                $loader->load($file);
            }
        }
    }
}
