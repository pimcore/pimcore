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

namespace Pimcore\Bundle\PersonalizationBundle\DependencyInjection;

use Pimcore\Bundle\PersonalizationBundle\Targeting\ActionHandler\DelegatingActionHandler;
use Pimcore\Bundle\PersonalizationBundle\Targeting\DataLoaderInterface;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Service\TargetingEnableService;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Storage\TargetingStorageInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class PimcorePersonalizationExtension extends ConfigurableExtension
{
    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $this->configureTargeting($container, $loader, $config['targeting']);
    }

    private function configureTargeting(ContainerBuilder $container, LoaderInterface $loader, array $config): void
    {
        $container->setParameter('pimcore_personalization.targeting.enabled', $config['enabled']);
        $container->setParameter('pimcore_personalization.targeting.conditions', $config['conditions']);
        // @phpstan-ignore-next-line
        if (!$container->hasParameter('pimcore.geoip.db_file')) {
            $container->setParameter('pimcore.geoip.db_file', '');
        }

        $loader->load('targeting.yaml');
        $loader->load('services.yaml');

        // set TargetingStorageInterface type hint to the configured service ID
        $container->setAlias(TargetingStorageInterface::class, $config['storage_id']);

        //register listeners
        $loader->load('targeting/services.yaml');
        $loader->load('targeting/listeners.yaml');

        $dataProviders = [];
        foreach ($config['data_providers'] as $dataProviderKey => $dataProviderServiceId) {
            $dataProviders[$dataProviderKey] = new Reference($dataProviderServiceId);
        }

        $dataProviderLocator = new Definition(ServiceLocator::class, [$dataProviders]);
        $dataProviderLocator
            ->setPublic(false)
            ->addTag('container.service_locator');

        $container
            ->findDefinition(DataLoaderInterface::class)
            ->setArgument('$dataProviders', $dataProviderLocator);

        $actionHandlers = [];
        foreach ($config['action_handlers'] as $actionHandlerKey => $actionHandlerServiceId) {
            $actionHandlers[$actionHandlerKey] = new Reference($actionHandlerServiceId);
        }

        $actionHandlerLocator = new Definition(ServiceLocator::class, [$actionHandlers]);
        $actionHandlerLocator
            ->setPublic(false)
            ->addTag('container.service_locator');

        $container
            ->getDefinition(DelegatingActionHandler::class)
            ->setArgument('$actionHandlers', $actionHandlerLocator);

        $container->setParameter('pimcore_personalization.targeting.session.enabled', $config['session']['enabled'] ?? false);

        $container
            ->findDefinition(TargetingEnableService::class)
            ->setArgument('$enabled', $config['enabled'] ?? false);
    }
}
