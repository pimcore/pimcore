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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\DependencyInjection;

use Pimcore\Analytics\Google\Config\SiteConfigProvider;
use Pimcore\Analytics\Google\Tracker as AnalyticsGoogleTracker;
use Pimcore\Bundle\CoreBundle\EventListener\TranslationDebugListener;
use Pimcore\DependencyInjection\ServiceCollection;
use Pimcore\Http\Context\PimcoreContextGuesser;
use Pimcore\Loader\ImplementationLoader\ClassMapLoader;
use Pimcore\Loader\ImplementationLoader\PrefixLoader;
use Pimcore\Model\Document\Editable\Loader\EditableLoader;
use Pimcore\Model\Document\Editable\Loader\PrefixLoader as DocumentEditablePrefixLoader;
use Pimcore\Model\Factory;
use Pimcore\Sitemap\EventListener\SitemapGeneratorListener;
use Pimcore\Targeting\ActionHandler\DelegatingActionHandler;
use Pimcore\Targeting\DataLoaderInterface;
use Pimcore\Targeting\Storage\TargetingStorageInterface;
use Pimcore\Translation\ExportDataExtractorService\DataExtractor\DataObjectDataExtractor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @internal
 */
final class PimcoreCoreExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    /**
     * @return string
     */
    public function getAlias()
    {
        return 'pimcore';
    }

    /**
     * {@inheritdoc}
     */
    public function loadInternal(array $config, ContainerBuilder $container)
    {
        // on container build the shutdown handler shouldn't be called
        // for details please see https://github.com/pimcore/pimcore/issues/4709
        \Pimcore::disableShutdown();

        // performance improvement, see https://github.com/symfony/symfony/pull/26276/files
        if (!$container->hasParameter('container.dumper.inline_class_loader')) {
            $container->setParameter('container.dumper.inline_class_loader', true);
        }

        // bundle manager/locator config
        $container->setParameter('pimcore.extensions.bundles.search_paths', $config['bundles']['search_paths']);
        $container->setParameter('pimcore.extensions.bundles.handle_composer', $config['bundles']['handle_composer']);

        // unauthenticated routes do not double-check for authentication
        $container->setParameter('pimcore.admin.unauthenticated_routes', $config['admin']['unauthenticated_routes']);

        if (!$container->hasParameter('pimcore.encryption.secret')) {
            $container->setParameter('pimcore.encryption.secret', $config['encryption']['secret']);
        }

        $container->setParameter('pimcore.admin.session.attribute_bags', $config['admin']['session']['attribute_bags']);
        $container->setParameter('pimcore.admin.translations.path', $config['admin']['translations']['path']);

        $container->setParameter('pimcore.translations.admin_translation_mapping', $config['translations']['admin_translation_mapping']);

        $container->setParameter('pimcore.web_profiler.toolbar.excluded_routes', $config['web_profiler']['toolbar']['excluded_routes']);

        $container->setParameter('pimcore.response_exception_listener.render_error_document', $config['error_handling']['render_error_document']);

        $container->setParameter('pimcore.maintenance.housekeeping.cleanup_tmp_files_atime_older_than', $config['maintenance']['housekeeping']['cleanup_tmp_files_atime_older_than']);
        $container->setParameter('pimcore.maintenance.housekeeping.cleanup_profiler_files_atime_older_than', $config['maintenance']['housekeeping']['cleanup_profiler_files_atime_older_than']);

        $container->setParameter('pimcore.documents.default_controller', $config['documents']['default_controller']);
        $container->setParameter('pimcore.documents.web_to_print.default_controller_print_page', $config['documents']['web_to_print']['default_controller_print_page']);
        $container->setParameter('pimcore.documents.web_to_print.default_controller_print_container', $config['documents']['web_to_print']['default_controller_print_container']);

        // register pimcore config on container
        // TODO is this bad practice?
        // TODO only extract what we need as parameter?
        $container->setParameter('pimcore.config', $config);

        // set default domain for router to main domain if configured
        // this will be overridden from the request in web context but is handy for CLI scripts
        if (!empty($config['general']['domain'])) {
            $container->setParameter('router.request_context.host', $config['general']['domain']);
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');
        $loader->load('services_routing.yml');
        $loader->load('services_workflow.yml');
        $loader->load('extensions.yml');
        $loader->load('logging.yml');
        $loader->load('request_response.yml');
        $loader->load('l10n.yml');
        $loader->load('argument_resolvers.yml');
        $loader->load('implementation_factories.yml');
        $loader->load('documents.yml');
        $loader->load('event_listeners.yml');
        $loader->load('templating.yml');
        $loader->load('profiler.yml');
        $loader->load('migrations.yml');
        $loader->load('analytics.yml');
        $loader->load('sitemaps.yml');
        $loader->load('aliases.yml');
        $loader->load('image_optimizers.yml');
        $loader->load('maintenance.yml');
        $loader->load('commands.yml');
        $loader->load('cache.yml');
        $loader->load('marshaller.yml');

        $this->configureImplementationLoaders($container, $config);
        $this->configureModelFactory($container, $config);
        $this->configureRouting($container, $config['routing']);
        $this->configureTranslations($container, $config['translations']);
        $this->configureTargeting($container, $loader, $config['targeting']);
        $this->configurePasswordEncoders($container, $config);
        $this->configureAdapterFactories($container, $config['newsletter']['source_adapters'], 'pimcore.newsletter.address_source_adapter.factories');
        $this->configureAdapterFactories($container, $config['custom_report']['adapters'], 'pimcore.custom_report.adapter.factories');
        $this->configureGoogleAnalyticsFallbackServiceLocator($container);
        $this->configureSitemaps($container, $config['sitemaps']);

        $container->setParameter('pimcore.workflow', $config['workflows']);

        // load engine specific configuration only if engine is active
        $loader->load('templating_twig.yml');

        $this->addContextRoutes($container, $config['context']);
    }

    /**
     * @param ContainerBuilder $container
     * @param array $config
     */
    private function configureModelFactory(ContainerBuilder $container, array $config)
    {
        $service = $container->getDefinition(Factory::class);

        $classMapLoader = new Definition(ClassMapLoader::class, [$config['models']['class_overrides']]);
        $classMapLoader->setPublic(false);

        $classMapLoaderId = 'pimcore.model.factory.classmap_builder';
        $container->setDefinition($classMapLoaderId, $classMapLoader);

        $service->addMethodCall('addLoader', [new Reference($classMapLoaderId)]);
    }

    /**
     * Configure implementation loaders from config
     *
     * @param ContainerBuilder $container
     * @param array $config
     */
    private function configureImplementationLoaders(ContainerBuilder $container, array $config)
    {
        $services = [
            EditableLoader::class => [
                'config' => $config['documents']['editables'],
                'prefixLoader' => DocumentEditablePrefixLoader::class,
            ],
            'pimcore.implementation_loader.object.data' => [
                'config' => $config['objects']['class_definitions']['data'],
                'prefixLoader' => PrefixLoader::class,
            ],
            'pimcore.implementation_loader.object.layout' => [
                'config' => $config['objects']['class_definitions']['layout'],
                'prefixLoader' => PrefixLoader::class,
            ],
            'pimcore.implementation_loader.asset.metadata.data' => [
                'config' => $config['assets']['metadata']['class_definitions']['data'],
                'prefixLoader' => PrefixLoader::class,
            ],
        ];

        // read config and add map/prefix loaders if configured - makes sure only needed objects are built
        // loaders are defined as private services as we don't need them outside the main type loader
        foreach ($services as $serviceId => $cfg) {
            $loaders = [];

            if ($cfg['config']['prefixes']) {
                $prefixLoader = new Definition($cfg['prefixLoader'], [$cfg['config']['prefixes']]);
                $prefixLoader->setPublic(false);

                $prefixLoaderId = $serviceId . '.prefix_loader';
                $container->setDefinition($prefixLoaderId, $prefixLoader);

                $loaders[] = new Reference($prefixLoaderId);
            }

            if ($cfg['config']['map']) {
                $classMapLoader = new Definition(ClassMapLoader::class, [$cfg['config']['map']]);
                $classMapLoader->setPublic(false);

                $classMapLoaderId = $serviceId . '.class_map_loader';
                $container->setDefinition($classMapLoaderId, $classMapLoader);

                $loaders[] = new Reference($classMapLoaderId);
            }

            $service = $container->getDefinition($serviceId);
            $service->setArguments([$loaders]);
        }
    }

    private function configureRouting(ContainerBuilder $container, array $config)
    {
        $container->setParameter(
            'pimcore.routing.static.locale_params',
            $config['static']['locale_params']
        );
    }

    private function configureTranslations(ContainerBuilder $container, array $config)
    {
        $parameter = $config['debugging']['parameter'];

        // remove the listener as it isn't needed at all if it is disabled or the parameter is empty
        if (!$config['debugging']['enabled'] || empty($parameter)) {
            $container->removeDefinition(TranslationDebugListener::class);
        } else {
            $definition = $container->getDefinition(TranslationDebugListener::class);
            $definition->setArgument('$parameterName', $parameter);
        }

        if (!empty($config['data_object']['translation_extractor']['attributes'])) {
            $definition = $container->getDefinition(DataObjectDataExtractor::class);
            $definition->setArgument('$exportAttributes', $config['data_object']['translation_extractor']['attributes']);
        }
    }

    private function configureTargeting(ContainerBuilder $container, LoaderInterface $loader, array $config)
    {
        $container->setParameter('pimcore.targeting.enabled', $config['enabled']);
        $container->setParameter('pimcore.targeting.conditions', $config['conditions']);
        if (!$container->hasParameter('pimcore.geoip.db_file')) {
            $container->setParameter('pimcore.geoip.db_file', '');
        }

        $loader->load('targeting.yml');

        // set TargetingStorageInterface type hint to the configured service ID
        $container->setAlias(TargetingStorageInterface::class, $config['storage_id']);

        if ($config['enabled']) {
            // enable targeting by registering listeners
            $loader->load('targeting/services.yml');
            $loader->load('targeting/listeners.yml');

            // add session support by registering the session configurator and session storage
            if ($config['session']['enabled']) {
                $loader->load('targeting/session.yml');
            }
        }

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
    }

    /**
     * Configures a "typed locator" (a class exposing get/has for a specific type) wrapping
     * a standard service locator. Example: Pimcore\Targeting\DataProviderLocator
     *
     * @param ContainerBuilder $container
     * @param string $locatorClass
     * @param array $services
     */
    private function configureTypedLocator(ContainerBuilder $container, string $locatorClass, array $services)
    {
        $serviceLocator = new Definition(ServiceLocator::class, [$services]);
        $serviceLocator
            ->setPublic(false)
            ->addTag('container.service_locator');

        $locator = $container->getDefinition($locatorClass);
        $locator->setArgument('$locator', $serviceLocator);
    }

    /**
     * Handle pimcore.security.encoder_factories mapping
     *
     * @param ContainerBuilder $container
     * @param array $config
     */
    private function configurePasswordEncoders(ContainerBuilder $container, array $config)
    {
        $definition = $container->findDefinition('pimcore.security.encoder_factory');

        $factoryMapping = [];
        foreach ($config['security']['encoder_factories'] as $className => $factoryConfig) {
            $factoryMapping[$className] = new Reference($factoryConfig['id']);
        }

        $definition->replaceArgument(1, $factoryMapping);
    }

    /**
     * Creates service locator which is used from static Pimcore\Google\Analytics class
     *
     * @param ContainerBuilder $container
     */
    private function configureGoogleAnalyticsFallbackServiceLocator(ContainerBuilder $container)
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

    private function configureSitemaps(ContainerBuilder $container, array $config)
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

    /**
     * Add context specific routes to context guesser
     *
     * @param ContainerBuilder $container
     * @param array $config
     */
    private function addContextRoutes(ContainerBuilder $container, array $config)
    {
        $guesser = $container->getDefinition(PimcoreContextGuesser::class);

        foreach ($config as $context => $contextConfig) {
            $guesser->addMethodCall('addContextRoutes', [$context, $contextConfig['routes']]);
        }
    }

    /**
     * Allows us to prepend/modify configurations of different extensions
     *
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        /*$securityConfigs = $container->getExtensionConfig('security');

        if (count($securityConfigs) > 1) {
            $this->setExtensionConfig($container, 'security', $securityConfigs);
        }*/
    }

    /**
     * Configure Adapter Factories
     *
     * @param ContainerBuilder $container
     * @param array $factories
     * @param string $serviceLocatorId
     */
    private function configureAdapterFactories(ContainerBuilder $container, $factories, $serviceLocatorId)
    {
        $serviceLocator = $container->getDefinition($serviceLocatorId);
        $arguments = [];

        foreach ($factories as $key => $serviceId) {
            $arguments[$key] = new Reference($serviceId);
        }

        $serviceLocator->setArgument(0, $arguments);
    }
}
