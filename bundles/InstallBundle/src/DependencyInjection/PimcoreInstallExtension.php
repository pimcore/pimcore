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

namespace Pimcore\Bundle\InstallBundle\DependencyInjection;

use Pimcore\Bundle\InstallBundle\Installer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @internal
 */
final class PimcoreInstallExtension extends ConfigurableExtension
{
    protected function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');

        $this->configureInstaller($container, $config);
    }

    private function configureInstaller(ContainerBuilder $container, array $config): void
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
