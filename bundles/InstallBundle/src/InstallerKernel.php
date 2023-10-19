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

namespace Pimcore\Bundle\InstallBundle;

use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

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
        return $this->projectRoot . '/var/log';
    }

    public function getCacheDir(): string
    {
        return $this->projectRoot . '/var/installer/cache';
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

    protected function configureContainer(ContainerConfigurator $configurator): void
    {
        $configurator->parameters()->set('secret', uniqid('installer-', true));
        $configurator->import('@PimcoreInstallBundle/config/config.yaml');

        // load installer config files if available
        foreach (['php', 'yaml', 'yml', 'xml'] as $extension) {
            $file = sprintf('%s/config/installer.%s', $this->getProjectDir(), $extension);

            if (file_exists($file)) {
                $configurator->import($file);
            }
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // nothing to do
    }
}
