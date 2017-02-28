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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\Config;
use Pimcore\Cache;
use Pimcore\Controller;
use Pimcore\Tool;
use Pimcore\File;
use Pimcore\Db;
use Pimcore\ExtensionManager;
use Pimcore\Model\User;
use Pimcore\Model;
use Pimcore\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Pimcore
{

    /**
     * @var bool
     */
    public static $adminMode;

    /**
     * @var bool
     */
    private static $inShutdown = false;

    /**
     * @var \Zend_EventManager_EventManager
     */
    private static $eventManager;

    /**
     * @var KernelInterface
     */
    private static $kernel;

    /**
     * @var \DI\Container
     */
    private static $diContainer;

    /**
     * @var array items to be excluded from garbage collection
     */
    private static $globallyProtectedItems;


    /**
     * @static
     */
    public static function setSystemRequirements()
    {
        // try to set system-internal variables

        $maxExecutionTime = 240;
        if (php_sapi_name() == "cli") {
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
        if (version_compare(PHP_VERSION, '5.6', "<")) {
            $m = "pimcore requires at least PHP version 5.6.0 your PHP version is: " . PHP_VERSION;
            Tool::exitWithError($m);
        }
    }

    /**
     * @static
     * @return \Pimcore\Config\Config|null
     */
    public static function initConfiguration()
    {
        $conf = null;

        // init configuration
        try {
            $conf = Config::getSystemConfig(true);

            // set timezone
            if ($conf instanceof \Pimcore\Config\Config) {
                if ($conf->general->timezone) {
                    date_default_timezone_set($conf->general->timezone);
                }
            }

            $debug = self::inDebugMode();

            if (!defined("PIMCORE_DEBUG")) {
                define("PIMCORE_DEBUG", $debug);
            }
            if (!defined("PIMCORE_DEVMODE")) {
                define("PIMCORE_DEVMODE", (bool) $conf->general->devmode);
            }
        } catch (\Exception $e) {
            $m = "Couldn't load system configuration";
            Logger::err($m);

            if (!defined("PIMCORE_DEBUG")) {
                define("PIMCORE_DEBUG", true);
            }
            if (!defined("PIMCORE_DEVMODE")) {
                define("PIMCORE_DEVMODE", false);
            }
        }

        // custom error logging in DEBUG mode & DEVMODE
        if (PIMCORE_DEVMODE || PIMCORE_DEBUG) {
            error_reporting(E_ALL & ~E_NOTICE);
        }

        return $conf;
    }

    /**
     * @static
     * @return bool
     */
    public static function inDebugMode()
    {
        if (defined("PIMCORE_DEBUG")) {
            return PIMCORE_DEBUG;
        }

        $conf = Config::getSystemConfig();
        $debug = (bool) $conf->general->debug;
        // enable debug mode only for one IP
        if ($conf->general->debug_ip && $conf->general->debug) {
            $debug = false;

            $debugIpAddresses = explode_and_trim(',', $conf->general->debug_ip);
            if (in_array(Tool::getClientIp(), $debugIpAddresses)) {
                $debug = true;
            }
        }

        return $debug;
    }

    /**
     * switches pimcore into the admin mode - there you can access also unpublished elements, ....
     * @static
     */
    public static function setAdminMode()
    {
        self::$adminMode = true;
    }

    /**
     * switches back to the non admin mode, where unpublished elements are invisible
     * @static
     */
    public static function unsetAdminMode()
    {
        self::$adminMode = false;
    }

    /**
     * check if the process is currently in admin mode or not
     * @static
     * @return bool
     */
    public static function inAdmin()
    {
        if (self::$adminMode !== null) {
            return self::$adminMode;
        }

        return false;
    }

    /**
     * @return \Zend_EventManager_EventManager
     */
    public static function getEventManager()
    {
        if (!self::$eventManager) {
            self::$eventManager = new \Zend_EventManager_EventManager();
        }

        return self::$eventManager;
    }

    /**
     * @return \DI\Container
     */
    public static function getDiContainer()
    {
        if (!self::$diContainer) {
            $builder = new \DI\ContainerBuilder();
            $builder->useAutowiring(false);
            $builder->useAnnotations(false);
            $builder->ignorePhpDocErrors(true);

            static::addDiDefinitions($builder);

            self::$diContainer = $builder->build();
        }

        return self::$diContainer;
    }

    /**
     * @return KernelInterface
     */
    public static function getKernel()
    {
        return static::$kernel;
    }

    /**
     * @return bool
     */
    public static function hasKernel() {
        if(static::$kernel) {
            return true;
        }

        return false;
    }

    /**
     * @param KernelInterface $kernel
     */
    public static function setKernel(KernelInterface $kernel)
    {
        static::$kernel = $kernel;
    }

    /**
     * Accessing the container this way is discouraged as dependencies should be wired through the container instead of
     * needing to access the container directly. This exists mainly for compatibility with legacy code.
     *
     * @return ContainerInterface
     */
    public static function getContainer()
    {
        return static::getKernel()->getContainer();
    }

    /**
     * @return bool
     */
    public static function hasContainer() {
        if(static::hasKernel()) {
            $container = static::getContainer();
            if ($container) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DI\Container $container
     */
    public static function setDiContainer(\DI\Container $container)
    {
        self::$diContainer = $container;
    }

    /**
     * @param \DI\ContainerBuilder $builder
     * @return \DI\Container
     */
    public static function addDiDefinitions(\DI\ContainerBuilder $builder)
    {
        $builder->addDefinitions(PIMCORE_PATH . "/config/di.php");

        $customFile = \Pimcore\Config::locateConfigFile("di.php");
        if (file_exists($customFile)) {
            $builder->addDefinitions($customFile);
        }

        self::getEventManager()->trigger('system.di.init', $builder);
    }

    /** Add $keepItems to the list of items which are protected from garbage collection.
     * @param $keepItems
     */
    public static function addToGloballyProtectedItems($keepItems)
    {
        if (is_string($keepItems)) {
            $keepItems = [$keepItems];
        }
        if (!is_array(self::$globallyProtectedItems) && $keepItems) {
            self::$globallyProtectedItems = [];
        }
        self::$globallyProtectedItems = array_merge(self::$globallyProtectedItems, $keepItems);
    }


    /** Items to be deleted.
     * @param $deleteItems
     */
    public static function removeFromGloballyProtectedItems($deleteItems)
    {
        if (is_string($deleteItems)) {
            $deleteItems = [$deleteItems];
        }

        if (is_array($deleteItems) && is_array(self::$globallyProtectedItems)) {
            foreach ($deleteItems as $item) {
                $key = array_search($item, self::$globallyProtectedItems);
                if ($key!==false) {
                    unset(self::$globallyProtectedItems[$key]);
                }
            }
        }
    }

    /**
     * Forces a garbage collection.
     * @static
     * @param array $keepItems
     */
    public static function collectGarbage($keepItems = [])
    {

        // close mysql-connection
        Db::close();

        $protectedItems = [
            "Zend_Locale",
            "Zend_View_Helper_Placeholder_Registry",
            "Zend_View_Helper_Doctype",
            "Zend_Translate",
            "Zend_Navigation",
            "pimcore_tag_block_current",
            "pimcore_tag_block_numeration",
            "Config_system",
            "pimcore_admin_user",
            "Config_website",
            "pimcore_editmode",
            "pimcore_error_document",
            "pimcore_site",
            "Pimcore_Db"
        ];

        if (is_array($keepItems) && count($keepItems) > 0) {
            $protectedItems = array_merge($protectedItems, $keepItems);
        }

        if (is_array(self::$globallyProtectedItems) && count(self::$globallyProtectedItems)) {
            $protectedItems = array_merge($protectedItems, self::$globallyProtectedItems);
        }

        Cache\Runtime::clear($protectedItems);

        if(class_exists("Pimcore\\Legacy")) {
            // @TODO: should be removed
            Pimcore\Legacy::collectGarbage($protectedItems);
        }

        Db::reset();

        // force PHP garbage collector
        gc_enable();
        $collectedCycles = gc_collect_cycles();

        Logger::debug("garbage collection finished, collected cycles: " . $collectedCycles);
    }

    /**
     * this method is called with register_shutdown_function() and writes all data queued into the cache
     * @static
     */
    public static function shutdown()
    {

        // set inShutdown to true so that the output-buffer knows that he is allowed to send the headers
        self::$inShutdown = true;
        

        // clear tags scheduled for the shutdown
        Cache::clearTagsOnShutdown();

        // write collected items to cache backend and remove the write lock
        if (php_sapi_name() != "cli") {
            // makes only sense for HTTP(S)
            // CLI are normally longer running scripts that tend to produce race conditions
            // so CLI scripts are not writing to the cache at all
            Cache::write();
        }
        Cache::removeWriteLock();

        // release all open locks from this process
        Model\Tool\Lock::releaseAll();
    }

    /**
     * @static
     *
     */
    public static function initLogger()
    {
        // special request log -> if parameter pimcore_log is set
        if (array_key_exists("pimcore_log", $_REQUEST) && self::inDebugMode()) {
            if (empty($_REQUEST["pimcore_log"])) {
                $requestLogName = date("Y-m-d_H-i-s");
            } else {
                $requestLogName = $_REQUEST["pimcore_log"];
            }

            $requestLogFile = PIMCORE_LOG_DIRECTORY . "/request-" . $requestLogName . ".log";
            if (!file_exists($requestLogFile)) {
                File::put($requestLogFile, "");
            }

            $requestDebugHandler = new \Monolog\Handler\StreamHandler($requestLogFile);

            foreach (self::getContainer()->getServiceIds() as $id) {
                if(strpos($id, "monolog.logger.") === 0) {
                    $logger = self::getContainer()->get($id);
                    if($logger->getName() != "event") {
                        // replace all handlers
                        $logger->setHandlers([$requestDebugHandler]);
                    }
                }
            }
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws Exception
     */
    public static function __callStatic($name, $arguments)
    {
        if(class_exists("Pimcore\\Legacy")) {
            return forward_static_call_array("Pimcore\\Legacy::" . $name, $arguments);
        }

        throw new \Exception("Call to undefined static method " . $name . " on class Pimcore");
    }
}
