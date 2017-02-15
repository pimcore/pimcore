<?php

namespace Pimcore;

use DI\Bridge\Symfony\Kernel as PhpDIKernel;
use Pimcore\Bundle\PimcoreAdminBundle\PimcoreAdminBundle;
use Pimcore\Bundle\PimcoreBundle\PimcoreBundle;
use Pimcore\Bundle\PimcoreLegacyBundle\PimcoreLegacyBundle;
use Sensio\Bundle\DistributionBundle\SensioDistributionBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

abstract class Kernel extends PhpDIKernel
{
    /**
     * Determines if the ZF1 stack should still be supported
     *
     * @var bool
     */
    protected $legacySupport = true;

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
            // new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new TwigBundle(),
            new MonologBundle(),
            new SensioFrameworkExtraBundle(),

            // CMF bundles
            new CmfRoutingBundle(),

            // pimcore bundles
            new PimcoreBundle(),
            new PimcoreAdminBundle(),
            new PimcoreLegacyBundle()
        ];

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
     * Boots the current kernel.
     */
    public function boot()
    {
        if (true === $this->booted) {
            return;
        }

        if ($this->loadClassCache) {
            $this->doLoadClassCache($this->loadClassCache[0], $this->loadClassCache[1]);
        }

        // handle system requirements
        $this->setSystemRequirements();

        // force load config
        $config = \Pimcore::initConfiguration();

        if ($this->legacySupport) {
            $this->setupTempDirectories();
        }

        // init bundles
        $this->initializeBundles();

        // init container
        $this->initializeContainer();

        \Pimcore::initLogger();

        // run website startup
        $this->runWebsiteStartup();

        // on pimcore shutdown
        register_shutdown_function(function () {
            \Pimcore::getEventManager()->trigger("system.shutdown");
        });

        // set up event handlers
        $this->setupEventHandlers();

        foreach ($this->getBundles() as $bundle) {
            $bundle->setContainer($this->container);
            $bundle->boot();
        }

        if ($config) {
            $this->initializePlugins();
        }

        $this->booted = true;
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
        mb_internal_encoding("UTF-8"); // only required for PHP 5.5, can be removed after 5.5 is unsupported by pimcore

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
        if (version_compare(PHP_VERSION, '5.6', "<")) {
            $m = "pimcore requires at least PHP version 5.6.0 your PHP version is: " . PHP_VERSION;
            Tool::exitWithError($m);
        }
    }

    /**
     * Try to set tmp directoy into superglobals, ZF and other frameworks (PEAR) sometimes relies on that
     */
    public function setupTempDirectories()
    {
        foreach (['TMPDIR', 'TEMP', 'TMP', 'windir', 'SystemRoot'] as $key) {
            $_ENV[$key] = PIMCORE_CACHE_DIRECTORY;
            $_SERVER[$key] = PIMCORE_CACHE_DIRECTORY;
        }
    }

    /**
     * Initialize legacy plugins
     */
    protected function initializePlugins()
    {
        \Pimcore::initPlugins(); // TODO move somewhere else?
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

    /**
     * Register pimcore event handlers - TODO how to handle ZF1/Symfony event dispatcher?
     */
    protected function setupEventHandlers()
    {
        // attach global shutdown event
        \Pimcore::getEventManager()->attach("system.shutdown", ["Pimcore", "shutdown"], 9999);

        // remove tags on asset delete
        \Pimcore::getEventManager()->attach("asset.postDelete", function (\Zend_EventManager_Event $e) {
            $asset = $e->getTarget();
            \Pimcore\Model\Element\Tag::setTagsForElement("asset", $asset->getId(), []);
        }, 9999);


        // attach workflow events to event handler
        \Pimcore::getEventManager()->attach(
            ["object.postAdd", "document.postAdd", "asset.postAdd"],
            ["\\Pimcore\\WorkflowManagement\\EventHandler", "elementPostAdd"]
        );

        \Pimcore::getEventManager()->attach(
            ["object.postDelete", "document.postDelete", "asset.postDelete"],
            ["\\Pimcore\\WorkflowManagement\\EventHandler", "elementPostDelete"]
        );

        \Pimcore::getEventManager()->attach(
            ["admin.object.get.preSendData", "admin.asset.get.preSendData", "admin.document.get.preSendData"],
            ["\\Pimcore\\WorkflowManagement\\EventHandler", "adminElementGetPreSendData"]
        );

        // backed search
        foreach (["asset", "object", "document"] as $type) {
            \Pimcore::getEventManager()->attach($type . ".postAdd", ["Pimcore\\Search\\EventHandler", "postAddElement"]);
            \Pimcore::getEventManager()->attach($type . ".postUpdate", ["Pimcore\\Search\\EventHandler", "postUpdateElement"]);
            \Pimcore::getEventManager()->attach($type . ".preDelete", ["Pimcore\\Search\\EventHandler", "preDeleteElement"]);
        }

        // UUID
        $conf = Config::getSystemConfig();
        if ($conf->general->instanceIdentifier) {
            foreach (["asset", "object", "document", "object.class"] as $type) {
                \Pimcore::getEventManager()->attach($type . ".postAdd", function ($e) {
                    \Pimcore\Model\Tool\UUID::create($e->getTarget());
                });

                \Pimcore::getEventManager()->attach($type . ".postDelete", function ($e) {
                    $uuidObject = \Pimcore\Model\Tool\UUID::getByItem($e->getTarget());
                    if ($uuidObject instanceof \Pimcore\Model\Tool\UUID) {
                        $uuidObject->delete();
                    }
                });
            }
        }
    }

    protected function buildPHPDIContainer(\DI\ContainerBuilder $builder)
    {
        \Pimcore::addDiDefinitions($builder);

        $container = $builder->build();

        \Pimcore::setDiContainer($container);

        return $container;
    }
}
