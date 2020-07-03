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

namespace Pimcore;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use FOS\JsRoutingBundle\FOSJsRoutingBundle;
use Pimcore\Bundle\AdminBundle\PimcoreAdminBundle;
use Pimcore\Bundle\CoreBundle\PimcoreCoreBundle;
use Pimcore\Bundle\GeneratorBundle\PimcoreGeneratorBundle;
use Pimcore\Cache\Runtime;
use Pimcore\Config\BundleConfigLocator;
use Pimcore\Event\SystemEvents;
use Pimcore\Extension\Bundle\Config\StateConfig;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\HttpKernel\BundleCollection\ItemInterface;
use Pimcore\HttpKernel\BundleCollection\LazyLoadedItem;
use Presta\SitemapBundle\PrestaSitemapBundle;
use Scheb\TwoFactorBundle\SchebTwoFactorBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;

abstract class Kernel extends SymfonyKernel
{
    /**
     * @var Extension\Config
     */
    protected $extensionConfig;

    /**
     * @var BundleCollection
     */
    private $bundleCollection;

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        return PIMCORE_APP_ROOT;
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectDir()
    {
        return PIMCORE_PROJECT_ROOT;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return PIMCORE_SYMFONY_CACHE_DIRECTORY . '/' . $this->getEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return PIMCORE_LOG_DIRECTORY;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $this->registerExtensionConfigFileResources($container);
        });

        //load system configuration
        $systemConfigFile = Config::locateConfigFile('system.yml');
        if (file_exists($systemConfigFile)) {
            $loader->load($systemConfigFile);
        }

        $bundleConfigLocator = new BundleConfigLocator($this);
        foreach ($bundleConfigLocator->locate('config') as $bundleConfig) {
            $loader->load($bundleConfig);
        }

        $configRealPath = realpath($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
        if ($configRealPath === false) {
            throw new InvalidConfigurationException('File ' . $this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml  cannot be found.');
        }
        $loader->load($configRealPath);
    }

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
     * @inheritdoc
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
     * @inheritdoc
     */
    public function shutdown()
    {
        if (true === $this->booted) {
            // cleanup runtime cache, doctrine, monolog ... to free some memory and avoid locking issues
            $this->container->get(\Pimcore\Helper\LongRunningHelper::class)->cleanUp();
        }

        return parent::shutdown();
    }

    /**
     * @inheritDoc
     */
    protected function initializeContainer()
    {
        parent::initializeContainer();

        // initialize runtime cache (defined as synthetic service)
        Runtime::getInstance();

        // set the extension config on the container
        $this->getContainer()->set(Extension\Config::class, $this->extensionConfig);

        \Pimcore::initLogger();
        \Pimcore\Cache::init();

        // on pimcore shutdown
        register_shutdown_function(function () {
            // check if container still exists at this point as it could already
            // be cleared (e.g. when running tests which boot multiple containers)
            if (null !== $container = $this->getContainer()) {
                $container->get('event_dispatcher')->dispatch(SystemEvents::SHUTDOWN);
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
            new SwiftmailerBundle(),
            new DoctrineBundle(),
            new SensioFrameworkExtraBundle(),
            new CmfRoutingBundle(),
            new PrestaSitemapBundle(),
            new SchebTwoFactorBundle(),
            new FOSJsRoutingBundle(),
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

            // PimcoreGeneratorBundle depends on SensioGeneratorBundle
            $generatorEnvironments = $this->getEnvironmentsForDevGeneratorBundles();
            $collection->addBundle(
                new PimcoreGeneratorBundle(),
                60,
                $generatorEnvironments
            );
        }
    }

    protected function getEnvironmentsForDevBundles(): array
    {
        return ['dev', 'test'];
    }

    protected function getEnvironmentsForDevGeneratorBundles(): array
    {
        return ['dev'];
    }

    /**
     * Registers bundles enabled via extension manager
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
        @ini_set('max_execution_time', $maxExecutionTime);
        @set_time_limit($maxExecutionTime);
        ini_set('default_charset', 'UTF-8');

        // set internal character encoding to UTF-8
        mb_internal_encoding('UTF-8');

        // this is for simple_dom_html
        ini_set('pcre.recursion-limit', 100000);

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

        // check some system variables
        $requiredVersion = '7.2';
        if (version_compare(PHP_VERSION, $requiredVersion, '<')) {
            $m = "pimcore requires at least PHP version $requiredVersion your PHP version is: " . PHP_VERSION;
            Tool::exitWithError($m);
        }
    }
}
