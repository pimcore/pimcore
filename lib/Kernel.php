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

namespace Pimcore;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use FOS\JsRoutingBundle\FOSJsRoutingBundle;
use League\FlysystemBundle\FlysystemBundle;
use Pimcore\Bundle\AdminBundle\PimcoreAdminBundle;
use Pimcore\Bundle\CoreBundle\PimcoreCoreBundle;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config\BundleConfigLocator;
use Pimcore\Event\SystemEvents;
use Pimcore\Extension\Bundle\Config\StateConfig;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\HttpKernel\BundleCollection\ItemInterface;
use Pimcore\HttpKernel\BundleCollection\LazyLoadedItem;
use Presta\SitemapBundle\PrestaSitemapBundle;
use Scheb\TwoFactorBundle\SchebTwoFactorBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

abstract class Kernel extends SymfonyKernel
{
    use MicroKernelTrait {
        registerContainerConfiguration as microKernelRegisterContainerConfiguration;

        registerBundles as microKernelRegisterBundles;
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @var Extension\Config
     */
    protected $extensionConfig;

    /**
     * @var BundleCollection
     */
    private $bundleCollection;

    /**
     * @deprecated
     */
    public function getRootDir()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.3',
            'Kernel::getRootDir() is deprecated and will be removed in Pimcore 11. Use Kernel::getProjectDir() instead.',
        );

        return PIMCORE_PROJECT_ROOT;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    #[\ReturnTypeWillChange]
    public function getProjectDir()// : string
    {
        return PIMCORE_PROJECT_ROOT;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    #[\ReturnTypeWillChange]
    public function getCacheDir()// : string
    {
        if (isset($_SERVER['APP_CACHE_DIR'])) {
            return $_SERVER['APP_CACHE_DIR'].'/'.$this->environment;
        }

        return PIMCORE_SYMFONY_CACHE_DIRECTORY . '/' . $this->environment;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    #[\ReturnTypeWillChange]
    public function getLogDir()// : string
    {
        return PIMCORE_LOG_DIRECTORY;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerConfigurator $container): void
    {
        $projectDir = realpath($this->getProjectDir());

        $container->import($projectDir . '/config/{packages}/*.yaml');
        $container->import($projectDir . '/config/{packages}/'.$this->environment.'/*.yaml');

        if (is_file($projectDir . '/config/services.yaml')) {
            $container->import($projectDir . '/config/services.yaml');
            $container->import($projectDir . '/config/{services}_'.$this->environment.'.yaml');
        } elseif (is_file($path = $projectDir . '/config/services.php')) {
            (require $path)($container->withPath($path), $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $projectDir = realpath($this->getProjectDir());

        $routes->import($projectDir . '/config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import($projectDir . '/config/{routes}/*.yaml');

        if (is_file($projectDir . '/config/routes.yaml')) {
            $routes->import($projectDir . '/config/routes.yaml');
        } elseif (is_file($path = $projectDir . '/config/routes.php')) {
            (require $path)($routes->withPath($path), $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $this->registerExtensionConfigFileResources($container);
        });

        $bundleConfigLocator = new BundleConfigLocator($this);
        foreach ($bundleConfigLocator->locate('config') as $bundleConfig) {
            $loader->load($bundleConfig);
        }

        $this->microKernelRegisterContainerConfiguration($loader);

        //load system configuration
        $systemConfigFile = Config::locateConfigFile('system.yml');
        if (file_exists($systemConfigFile)) {
            $loader->load($systemConfigFile);
        }

        $configArray = [
            [
                'storageDirectoryEnvVariableName' => 'PIMCORE_CONFIG_STORAGE_DIR_IMAGE_THUMBNAILS',
                'defaultStorageDirectoryName' => 'image-thumbnails',
            ],
            [
                'storageDirectoryEnvVariableName' => 'PIMCORE_CONFIG_STORAGE_DIR_VIDEO_THUMBNAILS',
                'defaultStorageDirectoryName' => 'video-thumbnails',
            ],
            [
                'storageDirectoryEnvVariableName' => 'PIMCORE_CONFIG_STORAGE_DIR_CUSTOM_REPORTS',
                'defaultStorageDirectoryName' => 'custom-reports',
            ],
            [
                'storageDirectoryEnvVariableName' => 'PIMCORE_CONFIG_STORAGE_DIR_DOCUMENT_TYPES',
                'defaultStorageDirectoryName' => 'document-types',
            ],
            [
                'storageDirectoryEnvVariableName' => 'PIMCORE_CONFIG_STORAGE_DIR_WEB_TO_PRINT',
                'defaultStorageDirectoryName' => 'web-to-print',
            ],
            [
                'storageDirectoryEnvVariableName' => 'PIMCORE_CONFIG_STORAGE_DIR_PREDEFINED_PROPERTIES',
                'defaultStorageDirectoryName' => 'predefined-properties',
            ],
            [
                'storageDirectoryEnvVariableName' => 'PIMCORE_CONFIG_STORAGE_DIR_PREDEFINED_ASSET_METADATA',
                'defaultStorageDirectoryName' => 'predefined-asset-metadata',
            ],
            [
                'storageDirectoryEnvVariableName' => 'PIMCORE_CONFIG_STORAGE_DIR_STATICROUTES',
                'defaultStorageDirectoryName' => 'staticroutes',
            ],
            [
                'storageDirectoryEnvVariableName' => 'PIMCORE_CONFIG_STORAGE_DIR_PERSPECTIVES',
                'defaultStorageDirectoryName' => 'perspectives',
            ],
            [
                'storageDirectoryEnvVariableName' => 'PIMCORE_CONFIG_STORAGE_DIR_CUSTOM_VIEWS',
                'defaultStorageDirectoryName' => 'custom-views',
            ],
        ];

        foreach ($configArray as $config) {
            $configDir = rtrim($_SERVER[$config['storageDirectoryEnvVariableName']] ?? PIMCORE_CONFIGURATION_DIRECTORY . '/' . $config['defaultStorageDirectoryName'], '/\\');
            $configDir = "$configDir/";
            if (is_dir($configDir)) {
                // @phpstan-ignore-next-line
                $loader->import($configDir);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     *
     * @deprecated Remove in Pimcore 11
     */
    private function registerExtensionConfigFileResources(ContainerBuilder $container)
    {
        $filenames = [
            'extensions.php',
            sprintf('extensions_%s.php', $this->getEnvironment()),
        ];

        $directories = [
            PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY,
            PIMCORE_CONFIGURATION_DIRECTORY,
        ];

        // add possible extensions.php files as file existence resources (only for the current env)
        foreach ($directories as $directory) {
            foreach ($filenames as $filename) {
                $container->addResource(new FileExistenceResource($directory . '/' . $filename));
            }
        }

        // add extensions.php as container resource
        if ($this->extensionConfig->configFileExists()) {
            $container->addResource(new FileResource($this->extensionConfig->locateConfigFile()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if (true === $this->booted) {
            // make sure container reset is handled properly
            parent::boot();

            return;
        }

        // handle system requirements
        $this->setSystemRequirements();

        // initialize extension manager config
        $this->extensionConfig = new Extension\Config();

        parent::boot();
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        if (true === $this->booted) {
            // cleanup runtime cache, doctrine, monolog ... to free some memory and avoid locking issues
            $this->container->get(\Pimcore\Helper\LongRunningHelper::class)->cleanUp();
        }

        parent::shutdown();
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeContainer()
    {
        parent::initializeContainer();

        // initialize runtime cache (defined as synthetic service)
        RuntimeCache::getInstance();

        // set the extension config on the container
        $this->getContainer()->set(Extension\Config::class, $this->extensionConfig);

        \Pimcore::initLogger();
        \Pimcore\Cache::init();

        // on pimcore shutdown
        register_shutdown_function(function () {
            // check if container still exists at this point as it could already
            // be cleared (e.g. when running tests which boot multiple containers)
            try {
                $container = $this->getContainer();
            } catch (\LogicException) {
                // Container is cleared. Allow tests to finish.
            }
            if (isset($container) && $container instanceof ContainerInterface) {
                $container->get('event_dispatcher')->dispatch(new GenericEvent(), SystemEvents::SHUTDOWN);
            }
            \Pimcore::shutdown();
        });
    }

    /**
     * Returns an array of bundles to register.
     *
     * @return BundleInterface[] An array of bundle instances
     */
    public function registerBundles(): array
    {
        $collection = $this->createBundleCollection();

        if (is_file($this->getProjectDir().'/config/bundles.php')) {
            $flexBundles = [];
            array_push($flexBundles, ...$this->microKernelRegisterBundles());
            $collection->addBundles($flexBundles);
        }

        // core bundles (Symfony, Pimcore)
        $this->registerCoreBundlesToCollection($collection);

        // custom bundles
        $this->registerBundlesToCollection($collection);

        // bundles registered in extensions.php
        $this->registerExtensionManagerBundles($collection);

        $bundles = $collection->getBundles($this->getEnvironment());

        $this->bundleCollection = $collection;

        return $bundles;
    }

    /**
     * Creates bundle collection. Use this method to set bundles on the collection
     * early.
     *
     * @return BundleCollection
     */
    protected function createBundleCollection(): BundleCollection
    {
        return new BundleCollection();
    }

    /**
     * Returns the bundle collection which was used to build the set of used bundles
     *
     * @return BundleCollection
     */
    public function getBundleCollection(): BundleCollection
    {
        return $this->bundleCollection;
    }

    /**
     * Registers "core" bundles
     *
     * @param BundleCollection $collection
     */
    protected function registerCoreBundlesToCollection(BundleCollection $collection)
    {
        $collection->addBundles([
            // symfony "core"/standard
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new MonologBundle(),
            new DoctrineBundle(),
            new DoctrineMigrationsBundle(),
            new SensioFrameworkExtraBundle(),
            new CmfRoutingBundle(),
            new PrestaSitemapBundle(),
            new SchebTwoFactorBundle(),
            new FOSJsRoutingBundle(),
            new FlysystemBundle(),
        ], 100);

        // pimcore bundles
        $collection->addBundles([
            new PimcoreCoreBundle(),
            new PimcoreAdminBundle(),
        ], 60);

        // load development bundles only in matching environments
        if (in_array($this->getEnvironment(), $this->getEnvironmentsForDevBundles(), true)) {
            $collection->addBundles([
                new DebugBundle(),
                new WebProfilerBundle(),
            ], 80);
        }
    }

    protected function getEnvironmentsForDevBundles(): array
    {
        return ['dev', 'test'];
    }

    /**
     * Registers bundles enabled via extension manager
     *
     * @deprecated will be removed in Pimcore 11
     *
     * @param BundleCollection $collection
     */
    protected function registerExtensionManagerBundles(BundleCollection $collection)
    {
        $stateConfig = new StateConfig($this->extensionConfig);

        foreach ($stateConfig->getEnabledBundles() as $className => $options) {
            if (!class_exists($className)) {
                continue;
            }

            // do not register bundles twice - skip if it was already loaded manually
            if ($collection->hasItem($className)) {
                continue;
            }

            // use lazy loaded item to instantiate the bundle only if environment matches
            $collection->add(new LazyLoadedItem(
                $className,
                $options['priority'],
                $options['environments'],
                ItemInterface::SOURCE_EXTENSION_MANAGER_CONFIG
            ));
        }
    }

    /**
     * Adds bundles to register to the bundle collection. The collection is able
     * to handle priorities and environment specific bundles.
     *
     * To be implemented in child classes
     *
     * @param BundleCollection $collection
     */
    public function registerBundlesToCollection(BundleCollection $collection)
    {
    }

    /**
     * Handle system settings and requirements
     */
    protected function setSystemRequirements()
    {
        // try to set system-internal variables
        $maxExecutionTime = 240;
        if (php_sapi_name() === 'cli') {
            $maxExecutionTime = 0;
        }

        //@ini_set("memory_limit", "1024M");
        @ini_set('max_execution_time', (string) $maxExecutionTime);
        @set_time_limit($maxExecutionTime);
        ini_set('default_charset', 'UTF-8');

        // set internal character encoding to UTF-8
        mb_internal_encoding('UTF-8');

        // zlib.output_compression conflicts with while (@ob_end_flush()) ;
        // see also: https://github.com/pimcore/pimcore/issues/291
        if (ini_get('zlib.output_compression')) {
            @ini_set('zlib.output_compression', 'Off');
        }

        // set dummy timezone if no tz is specified / required for example by the logger, ...
        $defaultTimezone = @date_default_timezone_get();
        if (!$defaultTimezone) {
            date_default_timezone_set('UTC'); // UTC -> default timezone
        }
    }

    /**
     * {@inheritdoc}
     */
    public function locateResource(string $name)
    {
        // BC layer for supporting both presta/sitemap-bundle": "^2.1 || ^3.2
        // @TODO to be removed in Pimcore 11
        if ($name === '@PrestaSitemapBundle/Resources/config/routing.yml') {
            try {
                // try the new location of v3 first, as most probably this is used
                return parent::locateResource('@PrestaSitemapBundle/config/routing.yml');
            } catch (\InvalidArgumentException $e) {
                // if the file doesnt exist in the new location, try the v2 location
                return parent::locateResource($name);
            }
        }

        return parent::locateResource($name);
    }
}
