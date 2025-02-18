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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection;

use InvalidArgumentException;
use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\TranslationDebugListener;
use Pimcore\Extension\Document\Areabrick\Attribute\AsAreabrick;
use Pimcore\Http\Context\PimcoreContextGuesser;
use Pimcore\Loader\ImplementationLoader\ClassMapLoader;
use Pimcore\Loader\ImplementationLoader\PrefixLoader;
use Pimcore\Model\Document\Editable\Loader\EditableLoader;
use Pimcore\Model\Document\Editable\Loader\PrefixLoader as DocumentEditablePrefixLoader;
use Pimcore\Model\Factory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @internal
 */
final class PimcoreCoreExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function getAlias(): string
    {
        return 'pimcore';
    }

    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        // on container build the shutdown handler shouldn't be called
        // for details please see https://github.com/pimcore/pimcore/issues/4709
        Pimcore::disableShutdown();

        // performance improvement, see https://github.com/symfony/symfony/pull/26276/files
        if (!$container->hasParameter('.container.dumper.inline_class_loader')) {
            $container->setParameter('.container.dumper.inline_class_loader', true);
        }

        // bundle manager/locator config
        $container->setParameter('pimcore.extensions.bundles.search_paths', $config['bundles']['search_paths']);
        $container->setParameter('pimcore.extensions.bundles.handle_composer', $config['bundles']['handle_composer']);

        if (!$container->hasParameter('pimcore.encryption.secret')) {
            $container->setParameter('pimcore.encryption.secret', $config['encryption']['secret']);
        }

        $container->setParameter('pimcore.translations.admin_translation_mapping', $config['translations']['admin_translation_mapping']);

        $container->setParameter('pimcore.web_profiler.toolbar.excluded_routes', $config['web_profiler']['toolbar']['excluded_routes']);

        $container->setParameter('pimcore.maintenance.housekeeping.cleanup_tmp_files_atime_older_than', $config['maintenance']['housekeeping']['cleanup_tmp_files_atime_older_than']);
        $container->setParameter('pimcore.maintenance.housekeeping.cleanup_profiler_files_atime_older_than', $config['maintenance']['housekeeping']['cleanup_profiler_files_atime_older_than']);

        $container->setParameter('pimcore.documents.default_controller', $config['documents']['default_controller']);

        //twig security policy allowlist config
        $container->setParameter('pimcore.templating.twig.sandbox_security_policy.tags', $config['templating_engine']['twig']['sandbox_security_policy']['tags']);
        $container->setParameter('pimcore.templating.twig.sandbox_security_policy.filters', $config['templating_engine']['twig']['sandbox_security_policy']['filters']);
        $container->setParameter('pimcore.templating.twig.sandbox_security_policy.functions', $config['templating_engine']['twig']['sandbox_security_policy']['functions']);

        // register pimcore config on container
        // TODO is this bad practice?
        // TODO only extract what we need as parameter?
        $container->setParameter('pimcore.config', $config);

        // set default domain for router to main domain if configured
        // this will be overridden from the request in web context but is handy for CLI scripts
        $domain = $config['general']['domain'] ?? '';
        if ($domain) {
            // when not an env variable, check if the domain is valid
            if (
                !str_starts_with($domain, 'env_') &&
                !filter_var(idn_to_ascii($domain), FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            ) {
                throw new InvalidArgumentException(sprintf('Invalid main domain name "%s"', $domain));
            }
            $container->setParameter('router.request_context.host', $config['general']['domain']);
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');
        $loader->load('services_routing.yaml');
        $loader->load('services_workflow.yaml');
        $loader->load('extensions.yaml');
        $loader->load('request_response.yaml');
        $loader->load('l10n.yaml');
        $loader->load('argument_resolvers.yaml');
        $loader->load('class_resolvers.yaml');
        $loader->load('implementation_factories.yaml');
        $loader->load('documents.yaml');
        $loader->load('event_listeners.yaml');
        $loader->load('templating.yaml');
        $loader->load('templating_twig.yaml');
        $loader->load('profiler.yaml');
        $loader->load('migrations.yaml');
        $loader->load('aliases.yaml');
        $loader->load('image_optimizers.yaml');
        $loader->load('maintenance.yaml');
        $loader->load('commands.yaml');
        $loader->load('cache.yaml');
        $loader->load('marshaller.yaml');
        $loader->load('message_handler.yaml');
        $loader->load('class_builder.yaml');
        $loader->load('serializer.yaml');

        $this->configureImplementationLoaders($container, $config);
        $this->configureModelFactory($container, $config);
        $this->configureClassResolvers($container, $config);
        $this->configureRouting($container, $config['routing']);
        $this->configureTranslations($container, $config['translations']);
        $this->configurePasswordHashers($container, $config);

        $container->setParameter('pimcore.workflow', $config['workflows']);

        $this->addContextRoutes($container, $config['context']);

        $container->registerAttributeForAutoconfiguration(
            AsAreabrick::class,
            static function (ChildDefinition $definition, AsAreabrick $attribute): void {
                $definition->addTag('pimcore.area.brick', ['id' => $attribute->id]);
            },
        );
    }

    private function configureModelFactory(ContainerBuilder $container, array $config): void
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
     */
    private function configureImplementationLoaders(ContainerBuilder $container, array $config): void
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

    private function configureClassResolvers(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('pimcore.documents.classes.map', $this->flattenConfigurationForClassResolver($config['documents']['type_definitions']));
        $container->setParameter('pimcore.assets.classes.map', $this->flattenConfigurationForClassResolver($config['assets']['type_definitions']));
    }

    private function configureRouting(ContainerBuilder $container, array $config): void
    {
        $container->setParameter(
            'pimcore.routing.static.locale_params',
            $config['static']['locale_params']
        );
    }

    private function configureTranslations(ContainerBuilder $container, array $config): void
    {
        $parameter = $config['debugging']['parameter'];

        // remove the listener as it isn't needed at all if it is disabled or the parameter is empty
        if (!$config['debugging']['enabled'] || empty($parameter)) {
            $container->removeDefinition(TranslationDebugListener::class);
        } else {
            $definition = $container->getDefinition(TranslationDebugListener::class);
            $definition->setArgument('$parameterName', $parameter);
        }
    }

    /**
     * Handle pimcore.security.password_hasher_factories mapping
     */
    private function configurePasswordHashers(ContainerBuilder $container, array $config): void
    {
        $definition = $container->findDefinition('pimcore.security.password_hasher_factory');

        $factoryMapping = [];
        foreach ($config['security']['password_hasher_factories'] as $className => $factoryConfig) {
            $factoryMapping[$className] = new Reference($factoryConfig['id']);
        }

        $definition->replaceArgument(1, $factoryMapping);
    }

    /**
     * Add context specific routes to context guesser
     */
    private function addContextRoutes(ContainerBuilder $container, array $config): void
    {
        $guesser = $container->getDefinition(PimcoreContextGuesser::class);

        foreach ($config as $context => $contextConfig) {
            $guesser->addMethodCall('addContextRoutes', [$context, $contextConfig['routes']]);
        }
    }

    /**
     * Allows us to prepend/modify configurations of different extensions
     */
    public function prepend(ContainerBuilder $container): void
    {
        /*$securityConfigs = $container->getExtensionConfig('security');

        if (count($securityConfigs) > 1) {
            $this->setExtensionConfig($container, 'security', $securityConfigs);
        }*/
    }

    /**
     * Extract class definitions and prefixes if configuration has more than just a class definition
     */
    private function flattenConfigurationForClassResolver(array $configuration): array
    {
        $newConfiguration = [];

        if (isset($configuration['map'])) {
            foreach ($configuration['map'] as $type => $config) {
                if (isset($config['class'])) {
                    $newConfiguration[$type] = $config['class'];
                }
            }
        }

        return $newConfiguration;
    }
}
