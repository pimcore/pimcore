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
     * @var bool
     */
    private static $mvcPrepared = false;

    /**
     * @static

     * @param bool $returnResponse
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @return null|Zend_Controller_Response_Http
     * @throws Exception
     * @throws Zend_Controller_Router_Exception
     */
    public static function run($returnResponse = false, Zend_Controller_Request_Abstract $request = null, Zend_Controller_Response_Abstract $response = null)
    {
        if (!$returnResponse) {
            // enable the output-buffer, why? see in self::outputBufferStart()
            self::outputBufferStart();
        }

        // initialize cache
        Cache::init();

        $conf = Config::getSystemConfig();
        if (!$conf) {
            // redirect to installer if configuration isn't present
            if (!Tool::isInstaller()) {
                header("Location: /install/");
                exit;
            }
        }

        // init front controller
        $front = static::prepareMvc($returnResponse, $conf);
        $throwExceptions = static::throwMvcExceptions($conf);

        self::getEventManager()->trigger("system.startup", $front);

        return self::runDispatcher($front, $throwExceptions, $request, $response);
    }

    /**
     * Prepare the ZF MVC stack
     *
     * @param bool $returnResponse
     * @param null $conf
     * @return Zend_Controller_Front
     */
    public static function prepareMvc($returnResponse = false, $conf = null)
    {
        $front = \Zend_Controller_Front::getInstance();

        // make sure this method runs only once
        if (static::$mvcPrepared) {
            return $front;
        }

        if (null === $conf) {
            $conf = Config::getSystemConfig();
        }

        // detect frontend (website)
        $frontend = Tool::isFrontend();

        // init front controller
        if ($conf) {
            self::registerWhoopsErrorHandler($conf, $frontend);
        }

        self::registerFrontControllerPlugins($front, $frontend);
        self::initControllerFront($front);

        if ($returnResponse) {
            $front->returnResponse(true);
        }

        // set router
        $router = self::initRouter($front);

        // only do this if not frontend => performance issue
        if (!$frontend) {
            self::initBackendRouter($router, $conf);
            self::checkPluginRoutes();

            if ($conf) {
                self::handleAdminMainDomainRedirect($conf);
            }
        }

        static::setupZendViewRenderer();
        static::$mvcPrepared = true;

        return $front;
    }

    /**
     * Determine if MVC stack should throw exceptions
     *
     * @param Zend_Config|mixed|null $conf
     * @return bool
     */
    public static function throwMvcExceptions($conf = null)
    {
        $throwExceptions = false;

        if (null === $conf) {
            $conf = Config::getSystemConfig();
        }

        if (!$conf) {
            // not installed, we display all error messages
            $throwExceptions = true;
        }

        // throw exceptions also when in preview or in editmode (documents) to see it immediately when there's a problem with this page
        if (Tool::isFrontentRequestByAdmin()) {
            $user = \Pimcore\Tool\Authentication::authenticateSession();
            if ($user instanceof User) {
                $throwExceptions = true;
            }
        }

        return $throwExceptions;
    }

    /**
     * Run dispatcher
     *
     * This is also standard for /admin/ requests -> error handling is done in Pimcore_Controller_Action_Admin
     *
     * @param Zend_Controller_Front $front
     * @param bool $throwExceptions
     * @param Zend_Controller_Request_Abstract|null $request
     * @param Zend_Controller_Response_Abstract|null $response
     * @return void|Zend_Controller_Response_Abstract
     * @throws Exception
     * @throws Zend_Controller_Router_Exception
     */
    protected static function runDispatcher(
        Zend_Controller_Front $front,
        $throwExceptions,
        Zend_Controller_Request_Abstract $request = null,
        Zend_Controller_Response_Abstract $response = null
    ) {
        try {
            if (!PIMCORE_DEBUG && !$throwExceptions && !PIMCORE_DEVMODE) {
                @ini_set("display_errors", "Off");
                @ini_set("display_startup_errors", "Off");

                return $front->dispatch($request, $response);
            } else {
                @ini_set("display_errors", "On");
                @ini_set("display_startup_errors", "On");

                $front->throwExceptions(true);

                return $front->dispatch($request, $response);
            }
        } catch (\Zend_Controller_Router_Exception $e) {
            if (!headers_sent()) {
                header("HTTP/1.0 404 Not Found");
            }
            Logger::err($e);
            throw new \Zend_Controller_Router_Exception("No route, document, custom route or redirect is matching the request: " . $_SERVER["REQUEST_URI"] . " | \n" . "Specific ERROR: " . $e->getMessage());
        } catch (\Exception $e) {
            if (!headers_sent()) {
                header("HTTP/1.0 500 Internal Server Error");
            }
            throw $e;
        }
    }

    /**
     * Register Whoops error handler
     *
     * @param Zend_Config $conf
     * @param bool $frontend
     */
    protected static function registerWhoopsErrorHandler(Zend_Config $conf, $frontend)
    {
        if (self::inDebugMode() && $frontend && !$conf->general->disable_whoops && !defined("HHVM_VERSION")) {
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            if (\Whoops\Util\Misc::isAjaxRequest()) {
                $jsonErrorHandler = new \Whoops\Handler\JsonResponseHandler;
                $whoops->pushHandler($jsonErrorHandler);
            }

            $whoops->register();

            // add event handler before Pimcore::shutdown() to ensure fatal errors are handled by Whoops
            self::getEventManager()->attach("system.shutdown", [$whoops, "handleShutdown"], 10000);
        }
    }

    /**
     * Register front controller plugins
     *
     * @param Zend_Controller_Front $front
     * @param bool $frontend
     */
    protected static function registerFrontControllerPlugins(Zend_Controller_Front $front, $frontend)
    {
        $front->registerPlugin(new Controller\Plugin\ErrorHandler(), 1);
        $front->registerPlugin(new Controller\Plugin\Maintenance(), 2);

        // register general pimcore plugins for frontend
        if ($frontend) {
            $front->registerPlugin(new Controller\Plugin\Thumbnail(), 795);
            $front->registerPlugin(new Controller\Plugin\Less(), 799);
        }

        if (Tool::useFrontendOutputFilters(new \Zend_Controller_Request_Http())) {
            $front->registerPlugin(new Controller\Plugin\HybridAuth(), 792);
            $front->registerPlugin(new Controller\Plugin\QrCode(), 793);
            $front->registerPlugin(new Controller\Plugin\CommonFilesFilter(), 794);
            $front->registerPlugin(new Controller\Plugin\WysiwygAttributes(), 796);
            $front->registerPlugin(new Controller\Plugin\Webmastertools(), 797);
            $front->registerPlugin(new Controller\Plugin\Analytics(), 798);
            $front->registerPlugin(new Controller\Plugin\TagManagement(), 804);
            $front->registerPlugin(new Controller\Plugin\Targeting(), 805);
            $front->registerPlugin(new Controller\Plugin\EuCookieLawNotice(), 807);
            $front->registerPlugin(new Controller\Plugin\GoogleTagManager(), 810);
            $front->registerPlugin(new Controller\Plugin\HttpErrorLog(), 850);
            $front->registerPlugin(new Controller\Plugin\Cache(), 901); // for caching
        }
    }

    /**
     * Add global routes
     *
     * @param Zend_Controller_Front $front
     * @return Zend_Controller_Router_Interface|Zend_Controller_Router_Rewrite
     */
    protected static function initRouter(Zend_Controller_Front $front)
    {
        /** @var Zend_Controller_Router_Interface|Zend_Controller_Router_Rewrite $router */
        $router = $front->getRouter();

        // website route => custom router which check for a suitable document
        $routeFrontend = new Controller\Router\Route\Frontend();
        $router->addRoute('default', $routeFrontend);

        $front->setRouter($router);

        return $router;
    }

    /**
     * Add backend routes
     *
     * @param Zend_Controller_Router_Interface|Zend_Controller_Router_Rewrite $router
     * @param Zend_Config|null $conf
     */
    protected static function initBackendRouter(Zend_Controller_Router_Interface $router, $conf)
    {
        $routeAdmin = new \Zend_Controller_Router_Route(
            'admin/:controller/:action/*',
            [
                'module' => 'admin',
                "controller" => "index",
                "action" => "index"
            ]
        );

        $routeInstall = new \Zend_Controller_Router_Route(
            'install/:controller/:action/*',
            [
                'module' => 'install',
                "controller" => "index",
                "action" => "index"
            ]
        );

        $routeUpdate = new \Zend_Controller_Router_Route(
            'admin/update/:controller/:action/*',
            [
                'module' => 'update',
                "controller" => "index",
                "action" => "index"
            ]
        );

        $routeExtensions = new \Zend_Controller_Router_Route(
            'admin/extensionmanager/:controller/:action/*',
            [
                'module' => 'extensionmanager',
                "controller" => "index",
                "action" => "index"
            ]
        );

        $routeReports = new \Zend_Controller_Router_Route(
            'admin/reports/:controller/:action/*',
            [
                'module' => 'reports',
                "controller" => "index",
                "action" => "index"
            ]
        );

        $routePlugin = new \Zend_Controller_Router_Route(
            'plugin/:module/:controller/:action/*',
            [
                "controller" => "index",
                "action" => "index"
            ]
        );

        $routeWebservice = new \Zend_Controller_Router_Route(
            'webservice/:controller/:action/*',
            [
                "module" => "webservice",
                "controller" => "index",
                "action" => "index"
            ]
        );

        $routeSearchAdmin = new \Zend_Controller_Router_Route(
            'admin/search/:controller/:action/*',
            [
                "module" => "searchadmin",
                "controller" => "index",
                "action" => "index",
            ]
        );

        $router->addRoute("install", $routeInstall);
        $router->addRoute('plugin', $routePlugin);
        $router->addRoute('admin', $routeAdmin);
        $router->addRoute('update', $routeUpdate);
        $router->addRoute('extensionmanager', $routeExtensions);
        $router->addRoute('reports', $routeReports);
        $router->addRoute('searchadmin', $routeSearchAdmin);

        if ($conf instanceof \Zend_Config and $conf->webservice and $conf->webservice->enabled) {
            $router->addRoute('webservice', $routeWebservice);
        }
    }

    /**
     * Check if this request routes into a plugin, if so check if the plugin is enabled
     */
    protected static function checkPluginRoutes()
    {
        if (preg_match("@^/plugin/([^/]+)/.*@", $_SERVER["REQUEST_URI"], $matches)) {
            $pluginName = $matches[1];
            if (!Pimcore\ExtensionManager::isEnabled("plugin", $pluginName)) {
                \Pimcore\Tool::exitWithError("Plugin is disabled. To use this plugin please enable it in the extension manager!");
            }
        }
    }

    /**
     * Force the main (default) domain for "admin" requests
     *
     * @param Zend_Config $conf
     */
    protected static function handleAdminMainDomainRedirect(Zend_Config $conf)
    {
        if ($conf->general->domain && $conf->general->domain != Tool::getHostname()) {
            $url = (($_SERVER['HTTPS'] == "on") ? "https" : "http") . "://" . $conf->general->domain . $_SERVER["REQUEST_URI"];
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $url, true, 301);
            exit;
        }
    }

    /**
     * @static
     * @param \Zend_Controller_Front $front
     */
    public static function initControllerFront(\Zend_Controller_Front $front)
    {

        // disable build-in error handler
        $front->setParam('noErrorHandler', true);

        // for admin an other modules directly in the core
        $front->addModuleDirectory(PIMCORE_PATH . "/modules");
        // for plugins
        if (is_dir(PIMCORE_PLUGINS_PATH) && is_readable(PIMCORE_PLUGINS_PATH)) {
            $front->addModuleDirectory(PIMCORE_PLUGINS_PATH);
        }

        // for frontend (default: website)
        $front->addControllerDirectory(PIMCORE_WEBSITE_PATH . "/controllers", PIMCORE_FRONTEND_MODULE);
        $front->setDefaultModule(PIMCORE_FRONTEND_MODULE);
    }

    /**
     * @static
     *
     */
    public static function initLogger()
    {

        // for forks, etc ...
        Logger::resetLoggers();

        // try to load configuration
        $conf = Config::getSystemConfig();

        if ($conf) {
            // redirect php error_log to /website/var/log/php.log
            if ($conf->general->custom_php_logfile) {
                $phpLog = PIMCORE_LOG_DIRECTORY . "/php.log";
                if (!file_exists($phpLog)) {
                    touch($phpLog);
                }
                if (is_writable($phpLog)) {
                    ini_set("error_log", $phpLog);
                    ini_set("log_errors", "1");
                }
            }
        }

        if (!is_file(PIMCORE_LOG_DEBUG)) {
            if (is_writable(dirname(PIMCORE_LOG_DEBUG))) {
                File::put(PIMCORE_LOG_DEBUG, "AUTOCREATE\n");
            }
        }

        $prios = [];
        $availablePrios = Logger::getAvailablePriorities();

        if ($conf && $conf->general->debugloglevel) {
            foreach ($availablePrios as $level) {
                $prios[] = $level;
                if ($level == $conf->general->debugloglevel) {
                    break;
                }
            }
            Logger::setPriorities($prios);
        } else {
            Logger::setVerbosePriorities();
        }

        if (is_writable(PIMCORE_LOG_DEBUG)) {
            // set default core logger (debug.log)
            if (!empty($prios)) {
                $loggerFile = new \Monolog\Logger('core');
                $loggerFile->pushHandler(new \Monolog\Handler\StreamHandler(PIMCORE_LOG_DEBUG));
                Logger::addLogger($loggerFile);
            }

            $conf = Config::getSystemConfig();
            if ($conf) {
                //email logger
                if (!empty($conf->general->logrecipient)) {
                    $user = User::getById($conf->general->logrecipient);
                    if ($user instanceof User && $user->isAdmin()) {
                        $email = $user->getEmail();
                        if (!empty($email)) {
                            $loggerMail = new \Monolog\Logger('email');
                            $mailHandler = new \Pimcore\Log\Handler\Mail($email);
                            $loggerMail->pushHandler(new \Monolog\Handler\BufferHandler($mailHandler));
                            Logger::addLogger($loggerMail);
                        }
                    }
                }
            }
        } else {
            // try to use syslog instead
            try {
                $loggerSyslog = new \Monolog\Logger('core');
                $loggerSyslog->pushHandler(new \Monolog\Handler\SyslogHandler("pimcore"));
                Logger::addLogger($loggerSyslog);
            } catch (\Exception $e) {
                // nothing to do here
            }
        }

        // special request log -> if parameter pimcore_log is set
        if (array_key_exists("pimcore_log", $_REQUEST) && self::inDebugMode()) {
            if (empty($_REQUEST["pimcore_log"])) {
                $requestLogName = date("Y-m-d_H-i-s");
            } else {
                $requestLogName = $_REQUEST["pimcore_log"];
            }

            $requestLogFile = dirname(PIMCORE_LOG_DEBUG) . "/request-" . $requestLogName . ".log";
            if (!file_exists($requestLogFile)) {
                File::put($requestLogFile, "");
            }

            $loggerRequest = new \Monolog\Logger('request');
            $loggerRequest->pushHandler(new \Monolog\Handler\StreamHandler($requestLogFile));
            Logger::addLogger($loggerRequest);

            Logger::setVerbosePriorities();
        }
    }

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
     * @return void
     * @deprecated
     */
    public static function initModules()
    {
        // only for compatibility reasons, will be removed in pimcore 5
    }

    /**
     *
     */
    public static function initPlugins()
    {
        // add plugin include paths

        $autoloader = \Zend_Loader_Autoloader::getInstance();

        try {
            $pluginConfigs = ExtensionManager::getPluginConfigs();
            if (!empty($pluginConfigs)) {
                $includePaths = [
                    get_include_path()
                ];

                //adding plugin include paths and namespaces
                if (count($pluginConfigs) > 0) {
                    foreach ($pluginConfigs as $p) {
                        if (!ExtensionManager::isEnabled("plugin", $p["plugin"]["pluginName"])) {
                            continue;
                        }

                        if (is_array($p['plugin']['pluginIncludePaths']['path'])) {
                            foreach ($p['plugin']['pluginIncludePaths']['path'] as $path) {
                                $includePaths[] = PIMCORE_PLUGINS_PATH . $path;
                            }
                        } elseif ($p['plugin']['pluginIncludePaths']['path'] != null) {
                            $includePaths[] = PIMCORE_PLUGINS_PATH . $p['plugin']['pluginIncludePaths']['path'];
                        }
                        if (is_array($p['plugin']['pluginNamespaces']['namespace'])) {
                            foreach ($p['plugin']['pluginNamespaces']['namespace'] as $namespace) {
                                $autoloader->registerNamespace($namespace);
                            }
                        } elseif ($p['plugin']['pluginNamespaces']['namespace'] != null) {
                            $autoloader->registerNamespace($p['plugin']['pluginNamespaces']['namespace']);
                        }
                    }
                }

                set_include_path(implode(PATH_SEPARATOR, $includePaths));

                /** @var \Pimcore\API\Plugin\Broker $broker */
                $broker = static::getKernel()->getContainer()->get('pimcore.plugin_broker');

                //registering plugins
                foreach ($pluginConfigs as $p) {
                    if (!ExtensionManager::isEnabled("plugin", $p["plugin"]["pluginName"])) {
                        continue;
                    }

                    $jsPaths = [];
                    $isExtJs6 = \Pimcore\Tool\Admin::isExtJS6();

                    if ($isExtJs6 && is_array($p['plugin']['pluginJsPaths-extjs6'])
                        && isset($p['plugin']['pluginJsPaths-extjs6']['path'])
                        && is_array($p['plugin']['pluginJsPaths-extjs6']['path'])) {
                        $jsPaths = $p['plugin']['pluginJsPaths-extjs6']['path'];
                    } elseif ($isExtJs6 && is_array($p['plugin']['pluginJsPaths-extjs6'])
                        && $p['plugin']['pluginJsPaths-extjs6']['path'] != null) {
                        $jsPaths[0] = $p['plugin']['pluginJsPaths-extjs6']['path'];
                    } elseif (is_array($p['plugin']['pluginJsPaths'])
                        && isset($p['plugin']['pluginJsPaths']['path'])
                        && is_array($p['plugin']['pluginJsPaths']['path'])) {
                        $jsPaths = $p['plugin']['pluginJsPaths']['path'];
                    } elseif (is_array($p['plugin']['pluginJsPaths'])
                        && $p['plugin']['pluginJsPaths']['path'] != null) {
                        $jsPaths[0] = $p['plugin']['pluginJsPaths']['path'];
                    }
                    //manipulate path for frontend
                    if (is_array($jsPaths) and count($jsPaths) > 0) {
                        for ($i = 0; $i < count($jsPaths); $i++) {
                            if (is_file(PIMCORE_PLUGINS_PATH . $jsPaths[$i])) {
                                $jsPaths[$i] = "/plugins" . $jsPaths[$i];
                            }
                        }
                    }

                    $cssPaths = [];
                    if ($isExtJs6 && is_array($p['plugin']['pluginCssPaths-extjs6'])
                        && isset($p['plugin']['pluginCssPaths-extjs6']['path'])
                        && is_array($p['plugin']['pluginCssPaths-extjs6']['path'])) {
                        $cssPaths = $p['plugin']['pluginCssPaths-extjs6']['path'];
                    } elseif ($isExtJs6 && is_array($p['plugin']['pluginCssPaths-extjs6'])
                        && $p['plugin']['pluginCssPaths-extjs6']['path'] != null) {
                        $cssPaths[0] = $p['plugin']['pluginCssPaths-extjs6']['path'];
                    } elseif (is_array($p['plugin']['pluginCssPaths'])
                        && isset($p['plugin']['pluginCssPaths']['path'])
                        && is_array($p['plugin']['pluginCssPaths']['path'])) {
                        $cssPaths = $p['plugin']['pluginCssPaths']['path'];
                    } elseif (is_array($p['plugin']['pluginCssPaths'])
                        && $p['plugin']['pluginCssPaths']['path'] != null) {
                        $cssPaths[0] = $p['plugin']['pluginCssPaths']['path'];
                    }

                    //manipulate path for frontend
                    if (is_array($cssPaths) and count($cssPaths) > 0) {
                        for ($i = 0; $i < count($cssPaths); $i++) {
                            if (is_file(PIMCORE_PLUGINS_PATH . $cssPaths[$i])) {
                                $cssPaths[$i] = "/plugins" . $cssPaths[$i];
                            }
                        }
                    }

                    try {
                        $className = $p['plugin']['pluginClassName'];
                        if (!empty($className) && Tool::classExists($className)) {
                            $plugin = new $className($jsPaths, $cssPaths);
                            if ($plugin instanceof \Pimcore\API\Plugin\AbstractPlugin) {
                                $broker->registerPlugin($plugin);
                            }
                        }
                    } catch (\Exception $e) {
                        Logger::err("Could not instantiate and register plugin [" . $p['plugin']['pluginClassName'] . "]");
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::alert("there is a problem with the plugin configuration");
            Logger::alert($e);
        }
    }

    /**
     * @static
     * @return Zend_Config|null
     */
    public static function initConfiguration()
    {
        $conf = null;

        // init configuration
        try {
            $conf = Config::getSystemConfig(true);

            // set timezone
            if ($conf instanceof \Zend_Config) {
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
     * @static
     */
    public static function setupZendViewRenderer()
    {
        // set custom view renderer
        $pimcoreViewHelper = new Controller\Action\Helper\ViewRenderer();
        \Zend_Controller_Action_HelperBroker::addHelper($pimcoreViewHelper);
    }

    /**
     * switches pimcore into the admin mode - there you can access also unpublished elements, ....
     * @static
     * @return void
     */
    public static function setAdminMode()
    {
        self::$adminMode = true;
    }

    /**
     * switches back to the non admin mode, where unpublished elements are invisible
     * @static
     * @return void
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
     * @param KernelInterface $kernel
     */
    public static function setKernel(KernelInterface $kernel)
    {
        static::$kernel = $kernel;
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
     * @return void
     */
    public static function collectGarbage($keepItems = [])
    {

        // close mysql-connection
        Db::close();
        Cache\Runtime::clear();

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

        $registryBackup = [];

        foreach ($protectedItems as $item) {
            if (\Zend_Registry::isRegistered($item)) {
                $registryBackup[$item] = \Zend_Registry::get($item);
            }
        }

        \Zend_Registry::_unsetInstance();

        foreach ($registryBackup as $key => $value) {
            \Zend_Registry::set($key, $value);
        }

        Db::reset();

        // force PHP garbage collector
        gc_enable();
        $collectedCycles = gc_collect_cycles();

        Logger::debug("garbage collection finished, collected cycles: " . $collectedCycles);
    }

    /**
     * this initiates the pimcore output-buffer, which is used to allow the registered shutdown-function
     * (created with register_shutdown_function) to run in the background without blocking the browser (loading indicator).
     * This is useful because the cache is written in the shutdown function and it takes sometimes a while to write the
     * max. 50 items into the cache (~ 1-10 secs depending on the backend and the data). Although all the content is
     * already arrived at the browser, he blocks the javascript execution (eg. jQuery's $(document).ready() ), because
     * the request is not finished or wasn't closed (sure the script is still running), what is really not necessary
     * This method is only called in Pimcore_Controller_Action_Frontend::init() to enable it only for frontend/website HTTP requests
     * - more infos see also self::outputBufferEnd()
     * @static
     * @return void
     */
    public static function outputBufferStart()
    {

        // only for HTTP(S)
        if (php_sapi_name() != "cli") {
            ob_start("\\Pimcore::outputBufferEnd");
        }
    }

    /**
     * if this method is called in self::shutdown() it forces the browser to close the connection an allows the
     * shutdown-function to run in the background
     * @static
     * @return string
     */
    public static function outputBufferEnd($data)
    {
        $output = null;
        $contentEncoding = null;

        if (headers_sent()) {
            return $data;
        }

        // cleanup admin session Set-Cookie headers if needed
        // a detailed description why this is necessary can be found in the doc-block of \Pimcore\Tool\Session::$sessionCookieCleanupNeeded
        if (Tool\Session::isSessionCookieCleanupNeeded()) {
            $headers = headers_list();
            $headers = array_reverse($headers);
            foreach ($headers as $header) {
                if (strpos($header, Tool\Session::getOption("name")) !== false) {
                    header($header, true); // setting the header again with 2nd arg = true, overrides all duplicates
                    break;
                }
            }
        }

        // only send this headers in the shutdown-function, so that it is also possible to get the contents of this buffer earlier without sending headers
        if (self::$inShutdown) {

            // force closing the connection at the client, this enables to do certain tasks (writing the cache) in the "background"
            header("Connection: close\r\n");

            // check for supported content-encodings
            if (strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") !== false) {
                $contentEncoding = "gzip";
            }

            if (!empty($data) && $contentEncoding) {
                ignore_user_abort(true);

                // find the content-type of the response
                $front = \Zend_Controller_Front::getInstance();
                $a = $front->getResponse()->getHeaders();
                $b = array_merge(headers_list(), $front->getResponse()->getRawHeaders());

                $contentType = null;

                // first check headers in headers_list() because they overwrite all other headers => see SOAP controller
                foreach ($b as $header) {
                    if (stripos($header, "content-type") !== false) {
                        $parts = explode(":", $header);
                        if (strtolower(trim($parts[0])) == "content-type") {
                            $contentType = trim($parts[1]);
                            break;
                        }
                    }
                }

                if (!$contentType) {
                    foreach ($a as $header) {
                        if (strtolower(trim($header["name"])) == "content-type") {
                            $contentType = $header["value"];
                            break;
                        }
                    }
                }

                // prepare the response to be sent (gzip or not)
                // do not add text/xml or a wildcard for text/* here because this causes problems with the SOAP server
                $gzipContentTypes = ["@text/html@i", "@application/json@", "@text/javascript@", "@text/css@"];
                $gzipIt = false;
                foreach ($gzipContentTypes as $type) {
                    if (@preg_match($type, $contentType)) {
                        $gzipIt = true;
                        break;
                    }
                }

                // gzip the contents and send connection close tthat the process can run in the background to finish
                // some tasks like writing the cache ...
                // using mb_strlen() because of PIMCORE-1509
                if ($gzipIt) {
                    $output = "\x1f\x8b\x08\x00\x00\x00\x00\x00" .
                        substr(gzcompress($data, 2), 0, -4) .
                        pack('V', crc32($data)) . // packing the CRC and the strlen is still required
                        pack('V', mb_strlen($data, "latin1")); // (although all modern browsers don't need it anymore) to work properly with google adwords check & co.

                    header("Content-Encoding: $contentEncoding\r\n");
                }
            }

            // no gzip/deflate encoding
            if (!$output) {
                $output = $data;
            }

            if (strlen($output) > 0) {
                // check here if there is actually content, otherwise readfile() and similar functions are not working anymore
                header("Content-Length: " . mb_strlen($output, "latin1"));
            }

            $vary = "Accept-Encoding";
            $deviceDetector = Tool\DeviceDetector::getInstance();
            if ($deviceDetector->wasUsed()) {
                $vary .= ", User-Agent";
            }
            header("Vary: " . $vary, false);

            header("X-Powered-By: pimcore", true);
        }

        // return the data unchanged
        return $output;
    }

    /**
     * this method is called with register_shutdown_function() and writes all data queued into the cache
     * @static
     * @return void
     */
    public static function shutdown()
    {

        // set inShutdown to true so that the output-buffer knows that he is allowed to send the headers
        self::$inShutdown = true;

        // flush all custom output buffers
        while (ob_get_level()) {
            ob_end_flush();
        }

        // flush everything
        flush();

        if (function_exists("fastcgi_finish_request")) {
            fastcgi_finish_request();
        }

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

        // disable logging - otherwise this will cause problems in the ongoing shutdown process (session write, __destruct(), ...)
        Logger::resetLoggers();
    }
}
