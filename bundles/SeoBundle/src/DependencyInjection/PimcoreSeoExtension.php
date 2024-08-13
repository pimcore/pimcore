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

namespace Pimcore\Bundle\SeoBundle\DependencyInjection;

use Pimcore\Bundle\SeoBundle\EventListener\SitemapGeneratorListener;
use Pimcore\DependencyInjection\ServiceCollection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class PimcoreSeoExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');
        $loader->load('maintenance.yaml');
        $loader->load('event_listeners.yaml');
        $loader->load('redirect_services.yaml');
        $loader->load('sitemap_services.yaml');
        $this->configureSitemaps($container, $config['sitemaps']);
        $container->setParameter('pimcore_seo.sitemaps', $config['sitemaps']);
        $container->setParameter('pimcore_seo.redirects', $config['redirects']);
    }

    private function configureSitemaps(ContainerBuilder $container, array $config): void
    {
        $listener = $container->getDefinition(SitemapGeneratorListener::class);

        $generators = [];
        if (isset($config['generators']) && !empty($config['generators'])) {
            $generators = $config['generators'];
        }

        uasort($generators, function (array $a, array $b) {
            if ($a['priority'] === $b['priority']) {
                return 0;
            }

            return $a['priority'] < $b['priority'] ? 1 : -1;
        });

        $mapping = [];
        foreach ($generators as $generatorName => $generatorConfig) {
            if (!$generatorConfig['enabled']) {
                continue;
            }

            $mapping[$generatorName] = new Reference($generatorConfig['generator_id']);
        }

        // the locator is a symfony core service locator containing every generator
        $locator = new Definition(ServiceLocator::class, [$mapping]);
        $locator->setPublic(false);
        $locator->addTag('container.service_locator');

        // the collection decorates the locator as iterable in the defined key order
        $collection = new Definition(ServiceCollection::class, [$locator, array_keys($mapping)]);
        $collection->setPublic(false);
        $listener->setArgument('$generators', $collection);
    }

    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('pimcore_admin')) {
            $loader = new YamlFileLoader(
                $container,
                new FileLocator(__DIR__ . '/../../config')
            );

            $loader->load('admin-classic.yaml');
        }
    }
}
