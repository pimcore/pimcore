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
use Pimcore\Bundle\PimcoreAdminBundle\PimcoreAdminBundle;
use Pimcore\Bundle\PimcoreBundle\PimcoreBundle;
use Pimcore\Event\SystemEvents;
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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

abstract class Kernel extends \Symfony\Component\HttpKernel\Kernel
{
    /**
     * @var array
     */
    protected $extensionManagerBundles = [];

    /**
     * Returns an array of bundles to register.
     *
     * @return BundleInterface[] An array of bundle instances
     */
    public function registerBundles()
    {
        $bundles = [
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

            // pimcore bundles
            new PimcoreBundle(),
            new PimcoreAdminBundle(),
        ];

        // bundles registered in extensions.php
        $bundles = $this->registerExtensionManagerBundles($bundles);

        // load environment specific bundles
        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new DebugBundle();
            $bundles[] = new WebProfilerBundle();
            $bundles[] = new SensioDistributionBundle();

            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new SensioGeneratorBundle();
            }
        }

        return $bundles;
    }

    /**
     * @param array $bundles
     *
     * @return array
     */
    protected function registerExtensionManagerBundles(array $bundles)
    {
        foreach ($this->extensionManagerBundles as $extensionManagerBundle) {
            $bundles[] = new $extensionManagerBundle();
        }

        return $bundles;
    }

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
        $extensionConfig = new Extension\Config();
        $this->processExtensionManagerConfig($extensionConfig);

        // init bundles
        $this->initializeBundles();

        // init container
        $this->initializeContainer();

        // set the extension config on the container
        $this->getContainer()->set('pimcore.extension.config', $extensionConfig);

        \Pimcore::initLogger();

        // run website startup
        $this->runWebsiteStartup();

        // on pimcore shutdown
        register_shutdown_function(function () {
            \Pimcore::getEventDispatcher()->dispatch(SystemEvents::SHUTDOWN);
            \Pimcore::shutdown();
        });

        foreach ($this->getBundles() as $bundle) {
            $bundle->setContainer($this->container);
            $bundle->boot();
        }

        $this->booted = true;
    }

    /**
     * Reads enabled bundles from extension manager config
     *
     * @param \Pimcore\Extension\Config $config
     */
    protected function processExtensionManagerConfig(Extension\Config $config)
    {
        $config = $config->loadConfig();
        if (isset($config->bundle)) {
            foreach ($config->bundle->toArray() as $bundleName => $state) {
                if ((bool) $state) {
                    $this->extensionManagerBundles[] = $bundleName;
                }
            }
        }
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
        @ini_set("max_execution_time", $maxExecutionTime);
        @set_time_limit($maxExecutionTime);
        ini_set('default_charset', "UTF-8");

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
            date_default_timezone_set("UTC"); // UTC -> default timezone
        }

        // check some system variables
        $requiredVersion = "7.0";
        if (version_compare(PHP_VERSION, $requiredVersion, "<")) {
            $m = "pimcore requires at least PHP version $requiredVersion your PHP version is: " . PHP_VERSION;
            Tool::exitWithError($m);
        }
    }

    /**
     * Run custom website startup
     */
    protected function runWebsiteStartup()
    {
        $websiteStartup = Config::locateConfigFile('startup.php');
        if (@is_file($websiteStartup)) {
            include_once $websiteStartup;
        }
    }
}
