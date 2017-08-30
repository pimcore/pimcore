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
use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Db;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Tool;
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
    private static $debugMode;

    /**
     * @var bool
     */
    private static $inShutdown = false;

    /**
     * @var KernelInterface
     */
    private static $kernel;

    /**
     * @var \Composer\Autoload\ClassLoader
     */
    private static $autoloader;

    /**
     * @var array items to be excluded from garbage collection
     */
    private static $globallyProtectedItems;

    /**
     * @static
     *
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

            if (!defined('PIMCORE_DEVMODE')) {
                define('PIMCORE_DEVMODE', (bool) $conf->general->devmode);
            }
        } catch (\Exception $e) {
            $m = "Couldn't load system configuration";
            Logger::err($m);

            if (!defined('PIMCORE_DEVMODE')) {
                define('PIMCORE_DEVMODE', false);
            }
        }

        $debug = self::inDebugMode();

        if (!defined('PIMCORE_DEBUG')) {
            define('PIMCORE_DEBUG', $debug);
        }

        // custom error logging in DEBUG mode & DEVMODE
        if (PIMCORE_DEVMODE || PIMCORE_DEBUG) {
            error_reporting(E_ALL & ~E_NOTICE);
        }

        return $conf;
    }

    /**
     * @static
     *
     * @return bool
     */
    public static function inDebugMode()
    {
        if (null !== self::$debugMode) {
            return self::$debugMode;
        }

        if (defined('PIMCORE_DEBUG')) {
            return PIMCORE_DEBUG;
        }

        $debug = false;

        $debugModeFile = PIMCORE_CONFIGURATION_DIRECTORY . '/debug-mode.php';
        if (file_exists($debugModeFile)) {
            $conf = include $debugModeFile;
            $debug = $conf['active'];

            // enable debug mode only for one IP
            if ($conf['ip'] && $debug) {
                $debug = false;

                $clientIp = Tool::getClientIp();
                if (null !== $clientIp) {
                    $debugIpAddresses = explode_and_trim(',', $conf['ip']);
                    if (in_array($clientIp, $debugIpAddresses)) {
                        $debug = true;
                    }
                }
            }
        }

        return $debug;
    }

    /**
     * Sets debug mode (overrides the PIMCORE_DEBUG constant and the debug mode from config)
     *
     * @param bool $debugMode
     */
    public static function setDebugMode(bool $debugMode = true)
    {
        self::$debugMode = (bool)$debugMode;
    }

    /**
     * switches pimcore into the admin mode - there you can access also unpublished elements, ....
     *
     * @static
     */
    public static function setAdminMode()
    {
        self::$adminMode = true;
    }

    /**
     * switches back to the non admin mode, where unpublished elements are invisible
     *
     * @static
     */
    public static function unsetAdminMode()
    {
        self::$adminMode = false;
    }

    /**
     * check if the process is currently in admin mode or not
     *
     * @static
     *
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
     * @return object|\Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    public static function getEventDispatcher()
    {
        return self::getContainer()->get('event_dispatcher');
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
    public static function hasKernel()
    {
        if (static::$kernel) {
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
    public static function hasContainer()
    {
        if (static::hasKernel()) {
            $container = static::getContainer();
            if ($container) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getAutoloader(): \Composer\Autoload\ClassLoader
    {
        return self::$autoloader;
    }

    /**
     * @param \Composer\Autoload\ClassLoader $autoloader
     */
    public static function setAutoloader(\Composer\Autoload\ClassLoader $autoloader)
    {
        self::$autoloader = $autoloader;
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
                if ($key !== false) {
                    unset(self::$globallyProtectedItems[$key]);
                }
            }
        }
    }

    /**
     * Forces a garbage collection.
     *
     * @static
     *
     * @param array $keepItems
     */
    public static function collectGarbage($keepItems = [])
    {

        // close mysql-connection
        Db::close();

        $protectedItems = [
            'Config_system',
            'pimcore_admin_user',
            'Config_website',
            'pimcore_editmode',
            'pimcore_error_document',
            'pimcore_site',
            'Pimcore_Db'
        ];

        if (is_array($keepItems) && count($keepItems) > 0) {
            $protectedItems = array_merge($protectedItems, $keepItems);
        }

        if (is_array(self::$globallyProtectedItems) && count(self::$globallyProtectedItems)) {
            $protectedItems = array_merge($protectedItems, self::$globallyProtectedItems);
        }

        Cache\Runtime::clear($protectedItems);

        if (class_exists('Pimcore\\Legacy')) {
            // @TODO: should be removed
            Pimcore\Legacy::collectGarbage($protectedItems);
        }

        Db::reset();

        // force PHP garbage collector
        gc_enable();
        $collectedCycles = gc_collect_cycles();

        Logger::debug('garbage collection finished, collected cycles: ' . $collectedCycles);
    }

    /**
     * this method is called with register_shutdown_function() and writes all data queued into the cache
     *
     * @static
     */
    public static function shutdown()
    {
        // set inShutdown to true so that the output-buffer knows that he is allowed to send the headers
        self::$inShutdown = true;

        // write and clean up cache
        if(php_sapi_name() != 'cli') {
            Cache::shutdown();
        }

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
        if (array_key_exists('pimcore_log', $_REQUEST) && self::inDebugMode()) {
            if (empty($_REQUEST['pimcore_log'])) {
                $requestLogName = date('Y-m-d_H-i-s');
            } else {
                $requestLogName = $_REQUEST['pimcore_log'];
            }

            $requestLogFile = PIMCORE_LOG_DIRECTORY . '/request-' . $requestLogName . '.log';
            if (!file_exists($requestLogFile)) {
                File::put($requestLogFile, '');
            }

            $requestDebugHandler = new \Monolog\Handler\StreamHandler($requestLogFile);

            foreach (self::getContainer()->getServiceIds() as $id) {
                if (strpos($id, 'monolog.logger.') === 0) {
                    $logger = self::getContainer()->get($id);
                    if ($logger->getName() != 'event') {
                        // replace all handlers
                        $logger->setHandlers([$requestDebugHandler]);
                    }
                }
            }
        }
    }

    /**
     * @return bool
     */
    public static function isLegacyModeAvailable()
    {
        return class_exists('Pimcore\\Legacy');
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function __callStatic($name, $arguments)
    {
        if (self::isLegacyModeAvailable()) {
            return forward_static_call_array('Pimcore\\Legacy::' . $name, $arguments);
        }

        throw new \Exception('Call to undefined static method ' . $name . ' on class Pimcore');
    }
}
