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

namespace Pimcore\Bundle\WebToPrintBundle\DependencyInjection;

use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

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
final class PimcoreWebToPrintExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');
        $container->setParameter('pimcore_web_to_print', $config);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $containerConfig = ConfigurationHelper::getConfigNodeFromSymfonyTree($container, 'pimcore_web_to_print');
        $configDir = $containerConfig['config_location']['web_to_print']['write_target']['options']['directory'];
        $configLoader = new YamlFileLoader(
            $container,
            new FileLocator($configDir)
        );

        //load configs
        $configs = ConfigurationHelper::getSymfonyConfigFiles($configDir);
        foreach ($configs as $config) {
            $configLoader->load($config);
        }
    }
}
