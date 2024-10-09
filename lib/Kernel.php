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

namespace Pimcore;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use FOS\JsRoutingBundle\FOSJsRoutingBundle;
use Knp\Bundle\PaginatorBundle\KnpPaginatorBundle;
use League\FlysystemBundle\FlysystemBundle;
use LogicException;
use Pimcore;
use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Pimcore\Bundle\CoreBundle\PimcoreCoreBundle;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config\BundleConfigLocator;
use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\Event\SystemEvents;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Scheb\TwoFactorBundle\SchebTwoFactorBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

abstract class Kernel extends SymfonyKernel
{
    use MicroKernelTrait {
        registerContainerConfiguration as microKernelRegisterContainerConfiguration;
        registerBundles as microKernelRegisterBundles;
        configureContainer as protected;
        configureRoutes as protected;
    }

    private BundleCollection $bundleCollection;

    public function getProjectDir(): string
    {
        return PIMCORE_PROJECT_ROOT;
    }

    public function getCacheDir(): string
    {
        return ($_SERVER['APP_CACHE_DIR'] ?? PIMCORE_SYMFONY_CACHE_DIRECTORY) . '/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return PIMCORE_LOG_DIRECTORY;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $bundleConfigLocator = new BundleConfigLocator($this);
        foreach ($bundleConfigLocator->locate('config') as $bundleConfig) {
            $loader->load($bundleConfig);
        }

        $this->microKernelRegisterContainerConfiguration($loader);

        $configKeysArray = [
            'image_thumbnails',
            'video_thumbnails',
            'document_types',
            'predefined_properties',
            'predefined_asset_metadata',
            'perspectives',
            'custom_views',
            'object_custom_layouts',
            'system_settings',
            'select_options',
        ];

        $loader->load(function (ContainerBuilder $container) use ($loader, $configKeysArray) {
            $containerConfig = ConfigurationHelper::getConfigNodeFromSymfonyTree($container, 'pimcore');

            foreach ($configKeysArray as $configKey) {
                $writeTargetConf = $containerConfig[LocationAwareConfigRepository::CONFIG_LOCATION][$configKey][LocationAwareConfigRepository::WRITE_TARGET];
                $readTargetConf = $containerConfig[LocationAwareConfigRepository::CONFIG_LOCATION][$configKey][LocationAwareConfigRepository::READ_TARGET] ?? null;

                $configDir = null;
                if ($readTargetConf !== null) {
                    if ($readTargetConf[LocationAwareConfigRepository::TYPE] === LocationAwareConfigRepository::LOCATION_SETTINGS_STORE ||
                        ($readTargetConf[LocationAwareConfigRepository::TYPE] !== LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG && $writeTargetConf[LocationAwareConfigRepository::TYPE] !== LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG)
                    ) {
                        continue;
                    }

                    if ($readTargetConf[LocationAwareConfigRepository::TYPE] === LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG && $readTargetConf[LocationAwareConfigRepository::OPTIONS][LocationAwareConfigRepository::DIRECTORY] !== null) {
                        $configDir = rtrim($readTargetConf[LocationAwareConfigRepository::OPTIONS][LocationAwareConfigRepository::DIRECTORY], '/\\');
                    }
                }

                if ($configDir === null) {
                    $configDir = rtrim($writeTargetConf[LocationAwareConfigRepository::OPTIONS][LocationAwareConfigRepository::DIRECTORY], '/\\');
                }
                $configDir = "$configDir/";
                if (is_dir($configDir)) {
                    // @phpstan-ignore-next-line
                    $loader->import($configDir);
                }
            }
        });
    }

    public function boot(): void
    {
        if (true === $this->booted) {
            // make sure container reset is handled properly
            parent::boot();

            return;
        }

        // handle system requirements
        $this->setSystemRequirements();

        parent::boot();
    }

    public function shutdown(): void
    {
        if (true === $this->booted) {
            // cleanup runtime cache, doctrine, monolog ... to free some memory and avoid locking issues
            $this->container->get(\Pimcore\Helper\LongRunningHelper::class)->cleanUp();
        }

        parent::shutdown();
    }

    protected function initializeContainer(): void
    {
        parent::initializeContainer();

        // initialize runtime cache (defined as synthetic service)
        RuntimeCache::getInstance();

        \Pimcore\Cache::init();

        // on pimcore shutdown
        register_shutdown_function(function () {
            // check if container still exists at this point as it could already
            // be cleared (e.g. when running tests which boot multiple containers)
            try {
                $container = $this->getContainer();
            } catch (LogicException) {
                // Container is cleared. Allow tests to finish.
            }
            if (isset($container) && $container instanceof ContainerInterface) {
                $container->get('event_dispatcher')->dispatch(new GenericEvent(), SystemEvents::SHUTDOWN);
            }
            Pimcore::shutdown();
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

        if (is_file($this->getBundlesPath())) {
            foreach ($this->microKernelRegisterBundles() as $flexBundle) {
                $collection->addBundle($flexBundle);
            }
        }

        // core bundles (Symfony, Pimcore)
        $this->registerCoreBundlesToCollection($collection);

        // custom bundles
        $this->registerBundlesToCollection($collection);

        $bundles = $collection->getBundles($this->getEnvironment());

        $this->bundleCollection = $collection;

        return $bundles;
    }

    /**
     * Creates bundle collection. Use this method to set bundles on the collection
     * early.
     *
     */
    protected function createBundleCollection(): BundleCollection
    {
        return new BundleCollection();
    }

    /**
     * Returns the bundle collection which was used to build the set of used bundles
     *
     */
    public function getBundleCollection(): BundleCollection
    {
        return $this->bundleCollection;
    }

    /**
     * Registers "core" bundles
     *
     */
    protected function registerCoreBundlesToCollection(BundleCollection $collection): void
    {
        $collection->addBundles([
            // symfony "core"/standard
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new TwigExtraBundle(),
            new MonologBundle(),
            new DoctrineBundle(),
            new DoctrineMigrationsBundle(),
            new CmfRoutingBundle(),
            new SchebTwoFactorBundle(),
            new FOSJsRoutingBundle(),
            new FlysystemBundle(),
            new KnpPaginatorBundle(),
        ], 100);

        // pimcore bundles
        $collection->addBundles([
            new PimcoreCoreBundle(),
        ], -10);

        // load development bundles only in matching environments
        if (in_array($this->getEnvironment(), $this->getEnvironmentsForDevBundles(), true)) {
            $collection->addBundles([
                new DebugBundle(),
                new WebProfilerBundle(),
            ], 80);
        }
    }

    /**
     * @return string[]
     */
    protected function getEnvironmentsForDevBundles(): array
    {
        return ['dev', 'test'];
    }

    /**
     * Adds bundles to register to the bundle collection. The collection is able
     * to handle priorities and environment specific bundles.
     *
     * To be implemented in child classes
     *
     */
    public function registerBundlesToCollection(BundleCollection $collection): void
    {
    }

    /**
     * Handle system settings and requirements
     */
    protected function setSystemRequirements(): void
    {
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
}
