<?php

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

namespace Pimcore\Bundle\GoogleMarketingBundle\DependencyInjection;

use Pimcore\Bundle\GoogleMarketingBundle\Config\SiteConfigProvider;
use Pimcore\Bundle\GoogleMarketingBundle\Tracker\Tracker as AnalyticsGoogleTracker;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class PimcoreGoogleMarketingExtension extends ConfigurableExtension
{
    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('services.yaml');
        $loader->load('analytics.yaml');
        $this->configureGoogleAnalyticsFallbackServiceLocator($container);

        $container->setParameter('pimcore_google_marketing', $config);
    }

    /**
     * Creates service locator which is used from static Pimcore\Google\Analytics class
     */
    private function configureGoogleAnalyticsFallbackServiceLocator(ContainerBuilder $container): void
    {
        $services = [
            AnalyticsGoogleTracker::class,
            SiteConfigProvider::class,
        ];

        $mapping = [];
        foreach ($services as $service) {
            $mapping[$service] = new Reference($service);
        }

        $serviceLocator = $container->getDefinition('pimcore.analytics.google.fallback_service_locator');
        $serviceLocator->setArguments([$mapping]);
    }
}
