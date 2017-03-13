<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection;

use Pimcore\Bundle\PimcoreBundle\Routing\Loader\AnnotatedRouteControllerLoader;
use Pimcore\Config\Config;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

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

        // unauthenticated routes do not double-check for authentication
        $container->setParameter('pimcore.admin.unauthenticated_routes', $config['admin']['unauthenticated_routes']);

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

        $this->configureCache($container, $loader, $config);

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
     * Configure pimcore core cache
     *
     * @param ContainerBuilder $container
     * @param LoaderInterface  $loader
     * @param array            $config
     */
    protected function configureCache(ContainerBuilder $container, LoaderInterface $loader, array $config)
    {
        $coreCachePool = null;
        if (null !== $config['cache']['pool_service_id']) {
            $coreCachePool = $config['cache']['pool_service_id'];
        }

        // default lifetime
        $container->setParameter('pimcore.cache.core.default_lifetime', $config['cache']['default_lifetime']);

        $loader->load('cache.yml');

        // register doctrine cache if it is enabled
        if ($config['cache']['pools']['doctrine']['enabled']) {
            $loader->load('cache_doctrine.yml');

            // load named connection
            $connectionId = sprintf('doctrine.dbal.%s_connection', $config['cache']['pools']['doctrine']['connection']);

            $doctrinePool = $container->findDefinition('pimcore.cache.core.pool.doctrine');
            $doctrinePool->replaceArgument(0, new Reference($connectionId));

            if (null === $coreCachePool) {
                $coreCachePool = 'pimcore.cache.core.pool.doctrine';
            }
        }

        // register redis cache if it is enabled
        if ($config['cache']['pools']['redis']['enabled']) {
            $container->setParameter(
                'pimcore.cache.core.redis.connection',
                $config['cache']['pools']['redis']['connection']
            );

            $container->setParameter(
                'pimcore.cache.core.redis.options',
                $config['cache']['pools']['redis']['options']
            );

            $loader->load('cache_redis.yml');

            if (null === $coreCachePool) {
                $coreCachePool = 'pimcore.cache.core.pool.redis';
            }
        }

        // default to filesystem cache
        if (null === $coreCachePool) {
            $coreCachePool = 'pimcore.cache.core.pool.filesystem';
        }

        // set core cache pool alias
        $container->setAlias('pimcore.cache.core.pool', $coreCachePool);
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
