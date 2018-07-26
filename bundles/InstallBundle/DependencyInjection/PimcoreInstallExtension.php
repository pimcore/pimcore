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

namespace Pimcore\Bundle\InstallBundle\DependencyInjection;

use Pimcore\Bundle\InstallBundle\Installer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class PimcoreInstallExtension extends ConfigurableExtension
{
    protected function loadInternal(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');

        $this->configureInstaller($container, $config);
    }

    private function configureInstaller(ContainerBuilder $container, array $config)
    {
        $parameters = $config['parameters'] ?? [];
        $definition = $container->getDefinition(Installer::class);

        $dbCredentials = $parameters['database_credentials'] ?? [];
        $dbCredentials = $this->normalizeDbCredentials($dbCredentials);

        if (!empty($dbCredentials)) {
            $definition->addMethodCall('setDbCredentials', [$dbCredentials]);
        }
    }

    /**
     * Only add DB credentials which are not empty
     *
     * @param array $dbCredentials
     *
     * @return array
     */
    private function normalizeDbCredentials(array $dbCredentials): array
    {
        $normalized = [];
        foreach ($dbCredentials as $key => $value) {
            if (!empty($value)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
