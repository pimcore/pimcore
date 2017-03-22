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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
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
            if (class_exists($extensionManagerBundle)) {
                $bundles[] = new $extensionManagerBundle();
            }
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
        $this->loadBundleConfigurations($loader);

        $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
    }

    /**
     * Try to autoload configs from bundles if Resources/config/pimcore exists.
     *
     * Will first try to load config_<environment>.<suffix> and fall back to config.<suffix> if the
     * environment specific lookup didn't find anything. All known suffixes are loaded, so if a config.yml
     * and a config.php exist, both will be used.
     *
     * @param LoaderInterface $loader
     */
    protected function loadBundleConfigurations(LoaderInterface $loader)
    {
        foreach ($this->bundles as $bundle) {
            $directory = $bundle->getPath() . '/Resources/config/pimcore';
            if (!(file_exists($directory) && is_dir($directory))) {
                continue;
            }

            // try to load environment specific file first, fall back to generic one if none found (config_dev.yml > config.yml)
            $finder = $this->buildContainerConfigFinder($directory, true);
            if ($finder->count() === 0) {
                $finder = $this->buildContainerConfigFinder($directory, false);
            }

            foreach ($finder as $file) {
                $loader->load($file->getRealPath());
            }
        }
    }

    /**
     * @param string $directory
     * @param bool $includeEnvironment
     *
     * @return Finder
     */
    protected function buildContainerConfigFinder($directory, $includeEnvironment = false)
    {
        $baseName = 'config';
        if ($includeEnvironment) {
            $baseName .= '_' . $this->getEnvironment();
        }

        $finder = new Finder();
        $finder->in($directory);

        foreach (['php', 'yml', 'xml'] as $extension) {
            $finder->name(sprintf('%s.%s', $baseName, $extension));
        }

        return $finder;
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
