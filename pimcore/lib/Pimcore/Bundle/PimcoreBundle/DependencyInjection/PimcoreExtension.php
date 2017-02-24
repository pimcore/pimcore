<?php

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection;

use Pimcore\Bundle\PimcoreBundle\Routing\Loader\AnnotatedRouteControllerLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
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

        // admin routes are used by PimcoreContextGuesser to determine the pimcore context (e.g. admin)
        $container->setParameter('pimcore.admin.routes', $config['admin']['routes']);
        // unauthenticated routes do not double-check for authentication
        $container->setParameter('pimcode.admin.unauthenticated_routes', $config['admin']['unauthenticated_routes']);

        $container->setParameter('pimcore.admin.translations.path', $config['admin']['translations']["path"]);

        // register pimcore config on container
        // TODO is this bad practice?
        // TODO only extract what we need as parameter?
        $container->setParameter('pimcore.config', $config);

        $this->setAnnotationRouteControllerLoader($container);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');
        $loader->load('event_listeners.yml');
        $loader->load('context_initializers.yml');
        $loader->load('templating.yml');
        $loader->load('profiler.yml');

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

    /**
     * Set annotation loader to our own implementation normalizing admin routes: converts the prefix
     * pimcore_pimcoreadmin_ to just pimcore_admin_
     *
     * @param ContainerBuilder $container
     */
    protected function setAnnotationRouteControllerLoader(ContainerBuilder $container)
    {
        $parameter = 'sensio_framework_extra.routing.loader.annot_class.class';

        // make sure the parameter is not dropped by sensio framework extra bundle
        // if this exception is thrown, implement the class override in a compiler pass
        if (!$container->hasParameter($parameter)) {
            throw new RuntimeException(sprintf(
                'The sensio framework extra bundle removed support for the "%s" parameter',
                $parameter
            ));
        }

        $container->setParameter($parameter, AnnotatedRouteControllerLoader::class);
    }
}
