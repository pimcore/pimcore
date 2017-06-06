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
use Pimcore\Bundle\AdminBundle\PimcoreAdminBundle;
use Pimcore\Bundle\CoreBundle\PimcoreCoreBundle;
use Pimcore\Bundle\GeneratorBundle\PimcoreGeneratorBundle;
use Pimcore\Cache\Runtime;
use Pimcore\Config\BundleConfigLocator;
use Pimcore\Event\SystemEvents;
use Pimcore\Extension\Bundle\Config\StateConfig;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\HttpKernel\BundleCollection\LazyLoadedItem;
use Pimcore\HttpKernel\Config\SystemConfigParamResource;
use Sensio\Bundle\DistributionBundle\SensioDistributionBundle;
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
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;

abstract class Kernel extends SymfonyKernel
{
    /**
     * @var Extension\Config
     */
    protected $extensionConfig;

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
    public function getCacheDir()
    {
        return PIMCORE_PRIVATE_VAR . '/cache/' . $this->getEnvironment();
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
        $bundleConfigLocator = new BundleConfigLocator($this);
        foreach ($bundleConfigLocator->locate('config') as $bundleConfig) {
            $loader->load($bundleConfig);
        }

        $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
    }

    /**
     * Boots the current kernel.
     */
    public function boot()
    {
        if (true === $this->booted) {
            return;
        }

        // handle system requirements
        $this->setSystemRequirements();

        // force load config
        \Pimcore::initConfiguration();

        // initialize extension manager config
        $this->extensionConfig = new Extension\Config();

        // init bundles
        $this->initializeBundles();

        // init container
        $this->initializeContainer();

        // initialize runtime cache (defined as synthetic service)
        Runtime::getInstance();

        // set the extension config on the container
        $this->getContainer()->set('pimcore.extension.config', $this->extensionConfig);

        \Pimcore::initLogger();
        \Pimcore\Cache::init();

        // on pimcore shutdown
        register_shutdown_function(function () {
            $this->getContainer()->get('event_dispatcher')->dispatch(SystemEvents::SHUTDOWN);
            \Pimcore::shutdown();
        });

        foreach ($this->getBundles() as $bundle) {
            $bundle->setContainer($this->container);
            $bundle->boot();
        }

        $this->booted = true;
    }

    /**
     * Returns an array of bundles to register.
     *
     * @return BundleInterface[] An array of bundle instances
     */
    public function registerBundles(): array
    {
        $collection = new BundleCollection();

        // core bundles (Symfony, Pimcore)
        $this->registerCoreBundlesToCollection($collection);

        // bundles registered in extensions.php
        $this->registerExtensionManagerBundles($collection);

        // custom bundles
        $this->registerBundlesToCollection($collection);

        $bundles = $collection->getBundles($this->getEnvironment());

        return $bundles;
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

            // CMF bundles
            new CmfRoutingBundle(),
        ], 100);

        // pimcore bundles
        $collection->addBundles([
            new PimcoreCoreBundle(),
            new PimcoreAdminBundle()
        ], 60);

        // environment specific dev + test bundles
        if (in_array($this->getEnvironment(), ['dev', 'test'])) {
            $collection->addBundles([
                new DebugBundle(),
                new WebProfilerBundle(),
                new SensioDistributionBundle()
            ], 80);

            // add generator bundle only if installed
            if (class_exists('Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle')) {
                $collection->addBundle(
                    new SensioGeneratorBundle(),
                    80, ['dev']
                );

                // PimcoreGeneratorBundle depends on SensioGeneratorBundle
                $collection->addBundle(
                    new PimcoreGeneratorBundle(),
                    60, ['dev']
                );
            }
        }
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

            // use lazy loaded item to instantiate the bundle only if environment matches
            $collection->add(new LazyLoadedItem($className, $options['priority'], $options['environments']));
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
     * @inheritDoc
     */
    protected function buildContainer()
    {
        $container = parent::buildContainer();

        // add system.php as container resource and extract config values into params
        $resource = new SystemConfigParamResource($container);
        $resource->register();
        $resource->setParameters();

        // add extensions.php as container resource
        if ($this->extensionConfig->configFileExists()) {
            $container->addResource(new FileResource($this->extensionConfig->locateConfigFile()));
        }

        return $container;
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

        error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

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
        $requiredVersion = '7.0';
        if (version_compare(PHP_VERSION, $requiredVersion, '<')) {
            $m = "pimcore requires at least PHP version $requiredVersion your PHP version is: " . PHP_VERSION;
            Tool::exitWithError($m);
        }
    }
}
