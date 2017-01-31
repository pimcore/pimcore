<?php

namespace PimcoreBundle\DependencyInjection;

use PimcoreBundle\DependencyInjection\Compiler\ZendViewHelperCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class PimcoreExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array $configs An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');
        $loader->load('event_listeners.yml');
        $loader->load('templating.yml');

        if ($container->hasParameter('templating.engines')) {
            $engines = $container->getParameter('templating.engines');

            foreach ($engines as $engine) {
                $loader->load(sprintf('templating_%s.yml', $engine));
            }
        }
    }
}
