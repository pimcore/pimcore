<?php 
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

use Pimcore\Config;
use Pimcore\Model\Cache;
use Pimcore\Controller;
use Pimcore\Tool; 
use Pimcore\File; 
use Pimcore\Resource;
use Pimcore\ExtensionManager;
use Pimcore\Model\User;
use Pimcore\Model;

class Pimcore {

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
     * @var array items to be excluded from garbage collection
     */
    private static $globallyProtectedItems;


    /**
     * @static
     * @throws Exception|\Zend_Controller_Router_Exception
     */
    public static function run() {

        self::setSystemRequirements();

        // detect frontend (website)
        $frontend = Tool::isFrontend();

        // enable the output-buffer, why? see in self::outputBufferStart()
        //if($frontend) {
        self::outputBufferStart();
        //}

        self::initAutoloader();
        self::initConfiguration();
        self::setupFramework();

        // config is loaded now init the real logger
        self::initLogger();

        // initialize cache
        Cache::init();

        // load plugins and modules (=core plugins)
        self::initModules();
        self::initPlugins();

        // init front controller
        $front = \Zend_Controller_Front::getInstance();

        $conf = Config::getSystemConfig();
        if(!$conf) {
            // redirect to installer if configuration isn't present
            if (!preg_match("/^\/install.*/", $_SERVER["REQUEST_URI"])) {
                header("Location: /install/");
                exit;
            }
        }

        if(self::inDebugMode() && $frontend && !$conf->general->disable_whoops && !defined("HHVM_VERSION")) {
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            $jsonErrorHandler = new \Whoops\Handler\JsonResponseHandler;
            $jsonErrorHandler->onlyForAjaxRequests(true);
            $whoops->pushHandler($jsonErrorHandler);
            $whoops->register();

            // add event handler before Pimcore::shutdown() to ensure fatal errors are handled by Whoops
            self::getEventManager()->attach("system.shutdown", array($whoops, "handleShutdown"), 10000);
        }

        $front->registerPlugin(new Controller\Plugin\ErrorHandler(), 1);
        $front->registerPlugin(new Controller\Plugin\Maintenance(), 2);

        // register general pimcore plugins for frontend
        if ($frontend) {
            $front->registerPlugin(new Controller\Plugin\Thumbnail(), 795);
            $front->registerPlugin(new Controller\Plugin\Less(), 799);
            $front->registerPlugin(new Controller\Plugin\AdminButton(), 806);
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
            $front->registerPlugin(new Controller\Plugin\HttpErrorLog(), 850);
            $front->registerPlugin(new Controller\Plugin\ContentLog(), 851);
            $front->registerPlugin(new Controller\Plugin\Cache(), 901); // for caching
        }

        self::initControllerFront($front);

        // set router
        $router = $front->getRouter();
        $routeAdmin = new \Zend_Controller_Router_Route(
            'admin/:controller/:action/*',
            array(
                'module' => 'admin',
                "controller" => "index",
                "action" => "index"
            )
        );
        $routeInstall = new \Zend_Controller_Router_Route(
            'install/:controller/:action/*',
            array(
                'module' => 'install',
                "controller" => "index",
                "action" => "index"
            )
        );
        $routeUpdate = new \Zend_Controller_Router_Route(
            'admin/update/:controller/:action/*',
            array(
                'module' => 'update',
                "controller" => "index",
                "action" => "index"
            )
        );
        $routePlugins = new \Zend_Controller_Router_Route(
            'admin/plugin/:controller/:action/*',
            array(
                'module' => 'pluginadmin',
                "controller" => "index",
                "action" => "index"
            )
        );
        $routeExtensions = new \Zend_Controller_Router_Route(
            'admin/extensionmanager/:controller/:action/*',
            array(
                'module' => 'extensionmanager',
                "controller" => "index",
                "action" => "index"
            )
        );
        $routeReports = new \Zend_Controller_Router_Route(
            'admin/reports/:controller/:action/*',
            array(
                'module' => 'reports',
                "controller" => "index",
                "action" => "index"
            )
        );
        $routePlugin = new \Zend_Controller_Router_Route(
            'plugin/:module/:controller/:action/*',
            array(
                "controller" => "index",
                "action" => "index"
            )
        );
        $routeWebservice = new \Zend_Controller_Router_Route(
            'webservice/:controller/:action/*',
            array(
                "module" => "webservice",
                "controller" => "index",
                "action" => "index"
            )
        );

        $routeSearchAdmin = new \Zend_Controller_Router_Route(
            'admin/search/:controller/:action/*',
            array(
                "module" => "searchadmin",
                "controller" => "index",
                "action" => "index",
            )
        );


        // website route => custom router which check for a suitable document
        $routeFrontend = new Controller\Router\Route\Frontend();


        $router->addRoute('default', $routeFrontend);

        // only do this if not frontend => performance issue
        if (!$frontend) {
            $router->addRoute("install", $routeInstall);
            $router->addRoute('plugin', $routePlugin);
            $router->addRoute('admin', $routeAdmin);
            $router->addRoute('update', $routeUpdate);
            $router->addRoute('plugins', $routePlugins);
            $router->addRoute('extensionmanager', $routeExtensions);
            $router->addRoute('reports', $routeReports);
            $router->addRoute('searchadmin', $routeSearchAdmin);
            if ($conf instanceof \Zend_Config and $conf->webservice and $conf->webservice->enabled) {
                    $router->addRoute('webservice', $routeWebservice);
            }

            // check if this request routes into a plugin, if so check if the plugin is enabled
            if (preg_match("@^/plugin/([^/]+)/.*@", $_SERVER["REQUEST_URI"], $matches)) {
                $pluginName = $matches[1];
                if(!Pimcore\ExtensionManager::isEnabled("plugin", $pluginName)) {
                    \Pimcore\Tool::exitWithError("Plugin is disabled. To use this plugin please enable it in the extension manager!");
                }
            }

            // force the main (default) domain for "admin" requests
            if($conf->general->domain && $conf->general->domain != Tool::getHostname()) {
                $url = (($_SERVER['HTTPS'] == "on") ? "https" : "http") . "://" . $conf->general->domain . $_SERVER["REQUEST_URI"];
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: " . $url, true, 301);
                exit;
            }
        }

        // check if webdav is configured and add router
        if ($conf instanceof \Zend_Config) {
            if ($conf->assets->webdav->hostname) {
                $routeWebdav = new \Zend_Controller_Router_Route_Hostname(
                    $conf->assets->webdav->hostname,
                    array(
                        "module" => "admin",
                        'controller' => 'asset',
                        'action' => 'webdav'
                    )
                );
                $router->addRoute('webdav', $routeWebdav);
            }
        }

        $front->setRouter($router);

        self::getEventManager()->trigger("system.startup", $front);

        // throw exceptions also when in preview or in editmode (documents) to see it immediately when there's a problem with this page
        $throwExceptions = false;
        if(Tool::isFrontentRequestByAdmin()) {
            $user = \Pimcore\Tool\Authentication::authenticateSession();
            if($user instanceof User) {
                $throwExceptions = true;
            }
        }

        // run dispatcher
        // this is also standard for /admin/ requests -> error handling is done in Pimcore_Controller_Action_Admin
        if (!PIMCORE_DEBUG && !$throwExceptions && !PIMCORE_DEVMODE) {
            @ini_set("display_errors", "Off");
            @ini_set("display_startup_errors", "Off");

            $front->dispatch();
        }
        else {
            @ini_set("display_errors", "On");
            @ini_set("display_startup_errors", "On");

            $front->throwExceptions(true);

            try {
                $front->dispatch();
            } catch (\Zend_Controller_Router_Exception $e) {
                if(!headers_sent()) {
                    header("HTTP/1.0 404 Not Found");
                }
                throw new \Zend_Controller_Router_Exception("No route, document, custom route or redirect is matching the request: " . $_SERVER["REQUEST_URI"] . " | \n" . "Specific ERROR: " . $e->getMessage());
            } catch (\Exception $e) {
                if(!headers_sent()) {
                    header("HTTP/1.0 500 Internal Server Error");
                }
                throw $e;
            }
        }
    }

    /**
     * @static
     * @param \Zend_Controller_Front $front
     */
    public static function initControllerFront (\Zend_Controller_Front $front) {

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
    public static function initLogger() {

        // for forks, etc ...
        \Logger::resetLoggers();

        // try to load configuration
        $conf = Config::getSystemConfig();

        if($conf) {
            // redirect php error_log to /website/var/log/php.log
            if($conf->general->custom_php_logfile) {
                $phpLog = PIMCORE_LOG_DIRECTORY . "/php.log";
                if(!file_exists($phpLog)) {
                    touch($phpLog);
                }
                if(is_writable($phpLog)) {
                    ini_set("error_log", $phpLog);
                    ini_set("log_errors", "1");
                }
            }
        }

        if(!is_file(PIMCORE_LOG_DEBUG)) {
            if(is_writable(dirname(PIMCORE_LOG_DEBUG))) {
                File::put(PIMCORE_LOG_DEBUG, "AUTOCREATE\n");
            }
        }

        $prioMapping = array(
            "debug" => \Zend_Log::DEBUG,
            "info" => \Zend_Log::INFO,
            "notice" => \Zend_Log::NOTICE,
            "warning" => \Zend_Log::WARN,
            "error" => \Zend_Log::ERR,
            "critical" => \Zend_Log::CRIT,
            "alert" => \Zend_Log::ALERT,
            "emergency" => \Zend_Log::EMERG
        );

        $prios = array();

        if($conf && $conf->general->debugloglevel) {
            $prioMapping = array_reverse($prioMapping);
            foreach ($prioMapping as $level => $state) {
                $prios[] = $prioMapping[$level];
                if($level == $conf->general->debugloglevel) {
                    break;
                }
            }
        }
        else {
            // log everything if config isn't loaded (eg. at the installer)
            foreach ($prioMapping as $p) {
                $prios[] = $p;
            }
        }

        \Logger::setPriorities($prios);

        if (is_writable(PIMCORE_LOG_DEBUG)) {
            
            // check for big logfile, empty it if it's bigger than about 200M
            if (filesize(PIMCORE_LOG_DEBUG) > 200000000) {
                rename(PIMCORE_LOG_DEBUG, PIMCORE_LOG_DEBUG . "-archive-" . date("m-d-Y-H-i")); // archive log (will be cleaned up by maintenance)
                File::put(PIMCORE_LOG_DEBUG, "");
            }

            if(!empty($prios)) {
                $writerFile = new \Zend_Log_Writer_Stream(PIMCORE_LOG_DEBUG);
                $loggerFile = new \Zend_Log($writerFile);
                \Logger::addLogger($loggerFile);
            }

            $conf = Config::getSystemConfig();
            if($conf) {
                //email logger
                if(!empty($conf->general->logrecipient)) {
                    $user = User::getById($conf->general->logrecipient);
                    if($user instanceof User && $user->isAdmin()) {
                        $email = $user->getEmail();
                        if(!empty($email)){
                            $mail = Tool::getMail(array($email),"pimcore log notification");
                            $mail->setIgnoreDebugMode(true);
                            if(!is_dir(PIMCORE_LOG_MAIL_TEMP)){
                                File::mkdir(PIMCORE_LOG_MAIL_TEMP);
                            }
                            $tempfile = PIMCORE_LOG_MAIL_TEMP."/log-".uniqid().".log";
                            $writerEmail = new \Pimcore\Log\Writer\Mail($tempfile,$mail);
                            $loggerEmail = new \Zend_Log($writerEmail);
                            \Logger::addLogger($loggerEmail);
                        }
                    }
                }
            }
        } else {
            // try to use syslog instead
            try {
                $writerSyslog = new \Zend_Log_Writer_Syslog(array('application' => 'pimcore'));
                $loggerSyslog = new \Zend_Log($writerSyslog);
                \Logger::addLogger($loggerSyslog);
            } catch (\Exception $e) {

            }
        }

        if(array_key_exists("pimcore_log", $_REQUEST) && self::inDebugMode()) {

            if(empty($_REQUEST["pimcore_log"])) {
                $requestLogName = date("Y-m-d_H-i-s");
            } else {
                $requestLogName = $_REQUEST["pimcore_log"];
            }

            $requestLogFile = dirname(PIMCORE_LOG_DEBUG) . "/request-" . $requestLogName . ".log";
            if(!file_exists($requestLogFile)) {
                File::put($requestLogFile,"");
            }

            $writerRequestLog = new \Zend_Log_Writer_Stream($requestLogFile);
            $loggerRequest = new \Zend_Log($writerRequestLog);
            \Logger::addLogger($loggerRequest);

            \Logger::setVerbosePriorities();
        }
    }

    /**
     * @static
     *
     */
    public static function setSystemRequirements() {
        // try to set system-internal variables

        $maxExecutionTime = 240;
        if(php_sapi_name() == "cli") {
            $maxExecutionTime = 0;
        }

        error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
        //@ini_set("memory_limit", "1024M");
        @ini_set("max_execution_time", $maxExecutionTime);
        @set_time_limit($maxExecutionTime);
        mb_internal_encoding("UTF-8");

        // this is for simple_dom_html
        ini_set('pcre.recursion-limit', 100000);

        // set dummy timezone if no tz is specified / required for example by the logger, ...
        $defaultTimezone = @date_default_timezone_get();
        if(!$defaultTimezone) {
            date_default_timezone_set("UTC"); // UTC -> default timezone
        }

        // check some system variables
        if (version_compare(PHP_VERSION, '5.4', "<")) {
            $m = "pimcore requires at least PHP version 5.4.0 your PHP version is: " . PHP_VERSION;
            Tool::exitWithError($m);
        }
    }

    /**
     * initialisze system modules and register them with the broker
     *
     * @static
     * @return void
     */
    public static function initModules() {

        $broker = \Pimcore\API\Plugin\Broker::getInstance();
        $broker->registerModule("\\Pimcore\\Model\\Search\\Backend\\Module");

        $conf = Config::getSystemConfig();
        if($conf->general->instanceIdentifier) {
            $broker->registerModule("\\Pimcore\\Model\\Tool\\UUID\\Module");
        }
    }

    /**
     *
     */
    public static function initPlugins() {
        // add plugin include paths

        $autoloader = \Zend_Loader_Autoloader::getInstance();

        try {

            $pluginConfigs = ExtensionManager::getPluginConfigs();
            if (!empty($pluginConfigs)) {

                $includePaths = array(
                    get_include_path()
                );

                //adding plugin include paths and namespaces
                if (count($pluginConfigs) > 0) {
                    foreach ($pluginConfigs as $p) {

                        if(!ExtensionManager::isEnabled("plugin", $p["plugin"]["pluginName"])){
                            continue;
                        }

                        if (is_array($p['plugin']['pluginIncludePaths']['path'])) {
                            foreach ($p['plugin']['pluginIncludePaths']['path'] as $path) {
                                $includePaths[] = PIMCORE_PLUGINS_PATH . $path;
                            }
                        }
                        else if ($p['plugin']['pluginIncludePaths']['path'] != null) {
                            $includePaths[] = PIMCORE_PLUGINS_PATH . $p['plugin']['pluginIncludePaths']['path'];
                        }
                        if (is_array($p['plugin']['pluginNamespaces']['namespace'])) {
                            foreach ($p['plugin']['pluginNamespaces']['namespace'] as $namespace) {
                                $autoloader->registerNamespace($namespace);
                    }
                        }
                        else if ($p['plugin']['pluginNamespaces']['namespace'] != null) {
                            $autoloader->registerNamespace($p['plugin']['pluginNamespaces']['namespace']);
                        }
                    }

                }

                set_include_path(implode(PATH_SEPARATOR, $includePaths));

                $broker = \Pimcore\API\Plugin\Broker::getInstance();

                //registering plugins
                foreach ($pluginConfigs as $p) {

                    if(!ExtensionManager::isEnabled("plugin", $p["plugin"]["pluginName"])){
                        continue;
                    }

                    $jsPaths = array();
                    if (is_array($p['plugin']['pluginJsPaths'])
                        && isset($p['plugin']['pluginJsPaths']['path'])
                        && is_array($p['plugin']['pluginJsPaths']['path'])) {
                        $jsPaths = $p['plugin']['pluginJsPaths']['path'];
                    }
                    else if (is_array($p['plugin']['pluginJsPaths'])
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

                    $cssPaths = array();
                    if (is_array($p['plugin']['pluginCssPaths'])
                        && isset($p['plugin']['pluginCssPaths']['path'])
                        && is_array($p['plugin']['pluginCssPaths']['path'])) {
                        $cssPaths = $p['plugin']['pluginCssPaths']['path'];
                    }
                    else if (is_array($p['plugin']['pluginCssPaths'])
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
                        \Logger::err("Could not instantiate and register plugin [" . $p['plugin']['pluginClassName'] . "]");
                    }

                }
                \Zend_Registry::set("Pimcore_API_Plugin_Broker", $broker);
            }
        }
        catch (\Exception $e) {
            \Logger::alert("there is a problem with the plugin configuration");
            \Logger::alert($e);
        }

    }

    /**
     * @static
     */
    public static function initAutoloader() {

        $autoloader = \Zend_Loader_Autoloader::getInstance();

        $autoloader->registerNamespace('Logger');
        $autoloader->registerNamespace('Pimcore');
        $autoloader->registerNamespace('Sabre');
        $autoloader->registerNamespace('Net_');
        $autoloader->registerNamespace('Website');
        $autoloader->registerNamespace('Csv');
        $autoloader->registerNamespace('Search');
        $autoloader->registerNamespace('Whoops');
        $autoloader->registerNamespace('Google');

        // these are necessary to be backward compatible
        // so if e.g. plugins use the namespace Object but do not include them in their own autoloader definition (plugin.xml)
        $autoloader->registerNamespace('Tool');
        $autoloader->registerNamespace('Webservice');
        $autoloader->registerNamespace('Element');
        $autoloader->registerNamespace('Thumbnail');
        $autoloader->registerNamespace('Staticroute');
        $autoloader->registerNamespace('Redirect');
        $autoloader->registerNamespace('Dependency');
        $autoloader->registerNamespace('Schedule');
        $autoloader->registerNamespace('Translation');
        $autoloader->registerNamespace('Glossary');
        $autoloader->registerNamespace('Document');
        $autoloader->registerNamespace('Object');
        $autoloader->registerNamespace('Asset');
        $autoloader->registerNamespace('User');
        $autoloader->registerNamespace('Property');
        $autoloader->registerNamespace('Version');
        $autoloader->registerNamespace('Site');

        Tool::registerClassModelMappingNamespaces();
    }

    /**
     * @static
     * @return bool
     */
    public static function initConfiguration() {
               
        // init configuration
        try {
            $conf = Config::getSystemConfig();

            // set timezone
            if ($conf instanceof \Zend_Config) {
                if ($conf->general->timezone) {
                    date_default_timezone_set($conf->general->timezone);
                }
            }

            $debug = self::inDebugMode();
            
            if (!defined("PIMCORE_DEBUG")) define("PIMCORE_DEBUG", $debug);
            if (!defined("PIMCORE_DEVMODE")) define("PIMCORE_DEVMODE", (bool) $conf->general->devmode);

            // check for output-cache settings
            // if a lifetime for the output cache is specified then the cache tag "output" will be ignored on clear
            $cacheLifetime = (int) $conf->cache->lifetime;
            if (!empty($cacheLifetime) && $conf->cache->enabled) {
                Cache::addIgnoredTagOnClear("output");
            }

            return true;
        }
        catch (\Exception $e) {
            $m = "Couldn't load system configuration";
            \Logger::err($m);
            
            //@TODO check here for /install otherwise exit here
        }

        if (!defined("PIMCORE_DEBUG")) define("PIMCORE_DEBUG", true);
        if (!defined("PIMCORE_DEVMODE")) define("PIMCORE_DEVMODE", false);

        // custom error logging in DEVMODE
        if(PIMCORE_DEVMODE) {
            error_reporting( (E_ALL ^ E_NOTICE) | E_STRICT);
            ini_set('error_log', PIMCORE_LOG_DIRECTORY . '/php.log');
        }
    }

    /**
     * @static
     * @return bool
     */
    public static function inDebugMode () {

        if(defined("PIMCORE_DEBUG")) {
            return PIMCORE_DEBUG;
        }

        $conf = Config::getSystemConfig();
        $debug = (bool) $conf->general->debug;
        // enable debug mode only for one IP
        if($conf->general->debug_ip && $conf->general->debug) {
            $debug = false;

            $debugIpAddresses = explode_and_trim(',',$conf->general->debug_ip);
            if(in_array(Tool::getClientIp(),$debugIpAddresses)) {
                $debug = true;
            }
        }

        return $debug;
    }

    /**
     * @static
     *
     */
    public static function setupFramework () {

        // try to set tmp directoy into superglobals, ZF and other frameworks (PEAR) sometimes relies on that
        foreach (array('TMPDIR', 'TEMP', 'TMP', 'windir', 'SystemRoot') as $key) {
            $_ENV[$key] = PIMCORE_CACHE_DIRECTORY;
            $_SERVER[$key] = PIMCORE_CACHE_DIRECTORY;
        }

        // set custom view renderer
        $pimcoreViewHelper = new Controller\Action\Helper\ViewRenderer();
        \Zend_Controller_Action_HelperBroker::addHelper($pimcoreViewHelper);
    }

    /**
     * switches pimcore into the admin mode - there you can access also unpublished elements, ....
     * @static
     * @return void
     */
    public static function setAdminMode () {
        self::$adminMode = true;
    }

    /**
     * switches back to the non admin mode, where unpublished elements are invisible
     * @static
     * @return void
     */
    public static function unsetAdminMode() {
        self::$adminMode = false;
    }

    /**
     * check if the process is currently in admin mode or not
     * @static
     * @return bool
     */
    public static function inAdmin () {

        if(self::$adminMode !== null) {
            return self::$adminMode;
        }

        return false;
    }

    /**
     * @return \Zend_EventManager_EventManager
     */
    public static function getEventManager() {
        if(!self::$eventManager) {
            self::$eventManager = new \Zend_EventManager_EventManager();
        }
        return self::$eventManager;
    }

    /** Add $keepItems to the list of items which are protected from garbage collection.
     * @param $keepItems
     */
    public static function addToGloballyProtectedItems($keepItems) {
        if (is_string($keepItems)) {
            $keepItems = array($keepItems);
        }
        if (!is_array(self::$globallyProtectedItems) && $keepItems) {
            self::$globallyProtectedItems = array();
        }
        self::$globallyProtectedItems = array_merge(self::$globallyProtectedItems, $keepItems);
    }


    /** Items to be deleted.
     * @param $deleteItems
     */
    public static function removeFromGloballyProtectedItems($deleteItems) {
        if (is_string($deleteItems)) {
            $deleteItems = array($deleteItems);
        }

        if (is_array($deleteItems) && is_array(self::$globallyProtectedItems)) {
            foreach ($deleteItems as $item) {
                $key = array_search($item,self::$globallyProtectedItems);
                if($key!==false){
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
    public static function collectGarbage ($keepItems = array()) {

        // close mysql-connection
        Resource::close();

        $protectedItems = array(
            "Zend_Locale",
            "Zend_View_Helper_Placeholder_Registry",
            "Zend_View_Helper_Doctype",
            "Zend_Translate",
            "Zend_Navigation",
            "Pimcore_API_Plugin_Broker",
            "pimcore_tag_block_current",
            "pimcore_tag_block_numeration",
            "Config_system",
            "pimcore_admin_user",
            "Config_website",
            "pimcore_editmode",
            "pimcore_error_document",
            "pimcore_site",
            "Pimcore_Resource_Mysql"
        );

        if(is_array($keepItems) && count($keepItems) > 0) {
            $protectedItems = array_merge($protectedItems, $keepItems);
        }

        if (is_array(self::$globallyProtectedItems) && count(self::$globallyProtectedItems)) {
            $protectedItems = array_merge($protectedItems, self::$globallyProtectedItems);
        }

        $registryBackup = array();

        foreach ($protectedItems as $item) {
            if(\Zend_Registry::isRegistered($item)) {
                $registryBackup[$item] = \Zend_Registry::get($item);
            }
        }

        \Zend_Registry::_unsetInstance();

        foreach ($registryBackup as $key => $value) {
            \Zend_Registry::set($key, $value);
        }

        Resource::reset();

        // force PHP garbage collector
        gc_enable();
        $collectedCycles = gc_collect_cycles();

        \Logger::debug("garbage collection finished, collected cycles: " . $collectedCycles);
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
    public static function outputBufferStart () {

        // only for HTTP(S)
        if(php_sapi_name() != "cli") {
            ob_start("\\Pimcore::outputBufferEnd");
        }
    }

    /**
     * if this method is called in self::shutdown() it forces the browser to close the connection an allows the
     * shutdown-function to run in the background
     * @static
     * @return string
     */
    public static function outputBufferEnd ($data) {

        $output = null;
        $contentEncoding = null;

        if(headers_sent()) {
            return $data;
        }

        // cleanup admin session Set-Cookie headers if needed
        // a detailed description why this is necessary can be found in the doc-block of \Pimcore\Tool\Session::$sessionCookieCleanupNeeded
        if(Tool\Session::isSessionCookieCleanupNeeded()) {
            $headers = headers_list();
            $headers = array_reverse($headers);
            foreach($headers as $header) {
                if(strpos($header, Tool\Session::getOption("name")) !== false) {
                    header($header, true); // setting the header again with 2nd arg = true, overrides all duplicates
                    break;
                }
            }
        }

        // force closing the connection at the client, this enables to do certain tasks (writing the cache) in the "background"
        header("Connection: close\r\n");

        // check for supported content-encodings
        if(strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") !== false) {
            $contentEncoding = "gzip";
        }

        // only send this headers in the shutdown-function, so that it is also possible to get the contents of this buffer earlier without sending headers
        if(self::$inShutdown && !headers_sent() && !empty($data) && $contentEncoding) {
            ignore_user_abort(true);

            // find the content-type of the response
            $front = \Zend_Controller_Front::getInstance();
            $a = $front->getResponse()->getHeaders();
            $b = array_merge(headers_list(), $front->getResponse()->getRawHeaders());

            $contentType = null;

            // first check headers in headers_list() because they overwrite all other headers => see SOAP controller
            foreach ($b as $header) {
                if(stripos($header, "content-type") !== false) {
                    $parts = explode(":", $header);
                    if(strtolower(trim($parts[0])) == "content-type") {
                        $contentType = trim($parts[1]);
                        break;
                    }
                }
            }

            if(!$contentType) {
                foreach ($a as $header) {
                    if(strtolower(trim($header["name"])) == "content-type") {
                        $contentType = $header["value"];
                        break;
                    }
                }
            }

            // prepare the response to be sent (gzip or not)
            // do not add text/xml or a wildcard for text/* here because this causes problems with the SOAP server
            $gzipContentTypes = array("@text/html@i","@application/json@","@text/javascript@","@text/css@");
            $gzipIt = false;
            foreach ($gzipContentTypes as $type) {
                if(@preg_match($type, $contentType)) {
                    $gzipIt = true;
                    break;
                }
            }

            // gzip the contents and send connection close tthat the process can run in the background to finish
            // some tasks like writing the cache ...
            // using mb_strlen() because of PIMCORE-1509
            if($gzipIt) {
                $output = "\x1f\x8b\x08\x00\x00\x00\x00\x00".
                    substr(gzcompress($data, 2), 0, -4).
                    pack('V', crc32($data)). // packing the CRC and the strlen is still required
                    pack('V', mb_strlen($data, "latin1")); // (although all modern browsers don't need it anymore) to work properly with google adwords check & co.

                header("Content-Encoding: $contentEncoding\r\n");
            }
        }

        // no gzip/deflate encoding
        if(!$output) {
            $output = $data;
        }

        if(strlen($output) > 0) {
            // check here if there is actually content, otherwise readfile() and similar functions are not working anymore
            header("Content-Length: " . mb_strlen($output, "latin1"));
        }
        header("X-Powered-By: pimcore");

        // return the data unchanged
        return $output;
    }

    /**
     * this method is called with register_shutdown_function() and writes all data queued into the cache
     * @static
     * @return void
     */
    public static function shutdown () {

        // set inShutdown to true so that the output-buffer knows that he is allowed to send the headers
        self::$inShutdown = true;

        // flush all custom output buffers
        while(@ob_end_flush());

        // flush everything
        flush();

        if(function_exists("fastcgi_finish_request")) {
            fastcgi_finish_request();
        }

        // clear tags scheduled for the shutdown
        Cache::clearTagsOnShutdown();

        // write collected items to cache backend and remove the write lock
        Cache::write();
        Cache::removeWriteLock();

        // release all open locks from this process
        Model\Tool\Lock::releaseAll();

        // disable logging - otherwise this will cause problems in the ongoing shutdown process (session write, __destruct(), ...)
        \Logger::resetLoggers();
    }
}

