<?php

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection;

use Pimcore\Bundle\PimcoreBundle\Routing\Loader\AnnotatedRouteControllerLoader;
use Pimcore\Config;
use Pimcore\HttpKernel\Config\PimcoreConfigResource;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
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
        // load system config as container params and rebuild
        // container in dev when pimcore config changes
        $this->loadPimcoreConfigParams($container);

        // TODO use ConfigurableExtension or getExtension()??
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

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

        $this->addContextRoutes($container, $config['context']);
    }

    /**
     * Add pimcore config as container parameter
     *
     * @param ContainerBuilder $container
     */
    protected function loadPimcoreConfigParams(ContainerBuilder $container)
    {
        $configResource = new PimcoreConfigResource();

        $container->addResource(new FileResource(Config::locateConfigFile('system.php')));
        $container->addResource($configResource);

        foreach ($configResource->getParameters() as $key => $value) {
            $container->setParameter($key, $value);
        }
    }

    /**
     * Add context specific routes to context guesser
     *
     * @param ContainerBuilder $container
     * @param array $config
     */
    protected function addContextRoutes(ContainerBuilder $container, array $config)
    {
        $guesser = $container->getDefinition('pimcore.service.context.pimcore_context_guesser');

        foreach ($config as $context => $contextConfig) {
            $guesser->addMethodCall('addContextRoutes', [$context, $contextConfig['routes']]);
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
