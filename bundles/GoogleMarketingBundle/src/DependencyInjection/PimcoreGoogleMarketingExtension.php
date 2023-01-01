<?php

namespace Pimcore\Bundle\GoogleMarketingBundle\DependencyInjection;

use Pimcore\Analytics\Google\Config\SiteConfigProvider;
use Pimcore\Analytics\Google\Tracker as AnalyticsGoogleTracker;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class PimcoreGoogleMarketingExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('services.yaml');
        $loader->load('event_listeners.yaml');
        $this->configureGoogleAnalyticsFallbackServiceLocator($container);

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
