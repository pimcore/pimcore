<?php

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class PimcoreExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // TODO use ConfigurableExtension or getExtension()??
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        // admin routes are used by RequestContextGuesser to determine the request context
        $container->setParameter('pimcore.admin.routes', $config['admin']['routes']);

        // unauthenticated routes do not double-check for authentication
        $container->setParameter('pimcode.admin.unauthenticated_routes', $config['admin']['unauthenticated_routes']);

        // register pimcore config on container
        // TODO is this bad practice?
        // TODO only extract what we need as parameter?
        $container->setParameter('pimcore.config', $config);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');
        $loader->load('event_listeners.yml');
        $loader->load('templating.yml');

        // load engine specific configuration only if engine is active
        $configuredEngines = ['twig', 'php'];

        if ($container->hasParameter('templating.engines')) {
            $engines = $container->getParameter('templating.engines');

            foreach ($engines as $engine) {
                if (in_array($engine, $configuredEngines)) {
                    $loader->load(sprintf('templating_%s.yml', $engine));
                }
            }
        }
    }
}
