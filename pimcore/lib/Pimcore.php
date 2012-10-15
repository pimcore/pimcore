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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

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
     * @static
     * @throws Exception|Zend_Controller_Router_Exception
     */
    public static function run() {

        self::setSystemRequirements();

        // detect frontend (website)
        $frontend = Pimcore_Tool::isFrontend();

        // enable the output-buffer, why? see in self::outputBufferStart()
        //if($frontend) {
        self::outputBufferStart();
        //}

        self::initAutoloader();
        self::initConfiguration();
        self::setupFramework();

        // config is loaded now init the real logger
        self::initLogger();

        // set locale data cache, this must be after self::initLogger() since Pimcore_Model_Cache requires the logger
        // to log if there's something wrong with the cache configuration in cache.xml
        Zend_Locale_Data::setCache(Pimcore_Model_Cache::getInstance());
        Zend_Locale::setCache(Pimcore_Model_Cache::getInstance());

        // load plugins and modules (=core plugins)
        self::initModules();
        self::initPlugins();

        // init front controller
        $front = Zend_Controller_Front::getInstance();

        $conf = Pimcore_Config::getSystemConfig();
        if(!$conf) {
            // redirect to installer if configuration isn't present
            if (!preg_match("/^\/install.*/", $_SERVER["REQUEST_URI"])) {
                header("Location: /install/");
                exit;
            }
        }

        $front->registerPlugin(new Pimcore_Controller_Plugin_ErrorHandler(), 1);
        $front->registerPlugin(new Pimcore_Controller_Plugin_Maintenance(), 2);

        // register general pimcore plugins for frontend
        if ($frontend) {
            $front->registerPlugin(new Pimcore_Controller_Plugin_Less(), 799);
        }

        if (Pimcore_Tool::useFrontendOutputFilters(new Zend_Controller_Request_Http())) {
            $front->registerPlugin(new Pimcore_Controller_Plugin_CommonFilesFilter(), 795);
            $front->registerPlugin(new Pimcore_Controller_Plugin_WysiwygAttributes(), 796);
            $front->registerPlugin(new Pimcore_Controller_Plugin_Webmastertools(), 797);
            $front->registerPlugin(new Pimcore_Controller_Plugin_Analytics(), 798);
            $front->registerPlugin(new Pimcore_Controller_Plugin_CssMinify(), 800);
            $front->registerPlugin(new Pimcore_Controller_Plugin_JavascriptMinify(), 801);
            $front->registerPlugin(new Pimcore_Controller_Plugin_ImageDataUri(), 803);
            $front->registerPlugin(new Pimcore_Controller_Plugin_TagManagement(), 804);
            $front->registerPlugin(new Pimcore_Controller_Plugin_Targeting(), 805);
            $front->registerPlugin(new Pimcore_Controller_Plugin_HttpErrorLog(), 850);
            $front->registerPlugin(new Pimcore_Controller_Plugin_Cache(), 901); // for caching
        }

        self::initControllerFront($front);

        // set router
        $router = $front->getRouter();
        $routeAdmin = new Zend_Controller_Router_Route(
            'admin/:controller/:action/*',
            array(
                'module' => 'admin',
                "controller" => "index",
                "action" => "index"
            )
        );
        $routeInstall = new Zend_Controller_Router_Route(
            'install/:controller/:action/*',
            array(
                'module' => 'install',
                "controller" => "index",
                "action" => "index"
            )
        );
        $routeUpdate = new Zend_Controller_Router_Route(
            'admin/update/:controller/:action/*',
            array(
                'module' => 'update',
                "controller" => "index",
                "action" => "index"
            )
        );
        $routePlugins = new Zend_Controller_Router_Route(
            'admin/plugin/:controller/:action/*',
            array(
                'module' => 'pluginadmin',
                "controller" => "index",
                "action" => "index"
            )
        );
        $routeExtensions = new Zend_Controller_Router_Route(
            'admin/extensionmanager/:controller/:action/*',
            array(
                'module' => 'extensionmanager',
                "controller" => "index",
                "action" => "index"
            )
        );
        $routeReports = new Zend_Controller_Router_Route(
            'admin/reports/:controller/:action/*',
            array(
                'module' => 'reports',
                "controller" => "index",
                "action" => "index"
            )
        );
        $routePlugin = new Zend_Controller_Router_Route(
            'plugin/:module/:controller/:action/*',
            array(
                "controller" => "index",
                "action" => "index"
            )
        );
        $routeWebservice = new Zend_Controller_Router_Route(
            'webservice/:controller/:action/*',
            array(
                "module" => "webservice",
                "controller" => "index",
                "action" => "index"
            )
        );

        $routeSearchAdmin = new Zend_Controller_Router_Route(
            'admin/search/:controller/:action/*',
            array(
                "module" => "searchadmin",
                "controller" => "index",
                "action" => "index",
            )
        );


        // website route => custom router which check for a suitable document
        $routeFrontend = new Pimcore_Controller_Router_Route_Frontend();


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
            if ($conf instanceof Zend_Config and $conf->webservice and $conf->webservice->enabled) {
                    $router->addRoute('webservice', $routeWebservice);
            }

            // force the main (default) domain for "admin" requests
            if($conf->general->domain && $conf->general->domain != Pimcore_Tool::getHostname()) {
                $url = (($_SERVER['HTTPS'] == "on") ? "https" : "http") . "://" . $conf->general->domain . $_SERVER["REQUEST_URI"];
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: " . $url, true, 301);
                exit;
            }
        }

        // check if webdav is configured and add router
        if ($conf instanceof Zend_Config) {
            if ($conf->assets->webdav->hostname) {
                $routeWebdav = new Zend_Controller_Router_Route_Hostname(
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

        Pimcore_API_Plugin_Broker::getInstance()->preDispatch();


        // throw exceptions also when in preview or in editmode (documents) to see it immediately when there's a problem with this page
        $throwExceptions = false;
        if(Pimcore_Tool::isFrontentRequestByAdmin()) {
            $user = Pimcore_Tool_Authentication::authenticateSession();
            if($user instanceof User) {
                $throwExceptions = true;
            }
        }

        // run dispatcher
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
            }
            catch (Zend_Controller_Router_Exception $e) {
                header("HTTP/1.0 404 Not Found");
                throw new Zend_Controller_Router_Exception("No route, document, custom route or redirect is matching the request: " . $_SERVER["REQUEST_URI"]);
            }
            catch (Exception $e) {
                header("HTTP/1.0 500 Internal Server Error");
                throw $e;
            }
        }
    }

    /**
     * @static
     * @param Zend_Controller_Front $front
     */
    public static function initControllerFront (Zend_Controller_Front $front) {

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
        Logger::resetLoggers();

        // try to load configuration
        $conf = Pimcore_Config::getSystemConfig();

        if($conf) {
            //firephp logger
            if($conf->general->firephp) {
                $writerFirebug = new Zend_Log_Writer_Firebug();
                $loggerFirebug = new Zend_Log($writerFirebug);
                Logger::addLogger($loggerFirebug);
            }
        }


        if(!is_file(PIMCORE_LOG_DEBUG)) {
            if(is_writable(dirname(PIMCORE_LOG_DEBUG))) {
                file_put_contents(PIMCORE_LOG_DEBUG, "AUTOCREATE\n");
            }
        }

        if (is_writable(PIMCORE_LOG_DEBUG)) {
            
            // check for big logfile, empty it if it's bigger than about 200M
            if (filesize(PIMCORE_LOG_DEBUG) > 200000000) {
                file_put_contents(PIMCORE_LOG_DEBUG, "");
            }

            $prioMapping = array(
                "debug" => Zend_Log::DEBUG,
                "info" => Zend_Log::INFO,
                "notice" => Zend_Log::NOTICE,
                "warning" => Zend_Log::WARN,
                "error" => Zend_Log::ERR,
                "critical" => Zend_Log::CRIT,
                "alert" => Zend_Log::ALERT,
                "emergency" => Zend_Log::EMERG
            );

            $prios = array();

            if($conf && $conf->general->loglevel) {
                $prioConf = $conf->general->loglevel->toArray();
                if(is_array($prioConf)) {
                    foreach ($prioConf as $level => $state) {
                        if($state) {
                            $prios[] = $prioMapping[$level];
                        }
                    }
                }
            }
            else {
                // log everything if config isn't loaded (eg. at the installer)
                foreach ($prioMapping as $p) {
                    $prios[] = $p;
                }
            }

            if(!empty($prios)) {
                $writerFile = new Zend_Log_Writer_Stream(PIMCORE_LOG_DEBUG);
                $loggerFile = new Zend_Log($writerFile);
                Logger::addLogger($loggerFile);

                Logger::setPriorities($prios);
            }

            $conf = Pimcore_Config::getSystemConfig();
            if($conf) {
                //email logger
                if(!empty($conf->general->logrecipient)) {
                    $user = User::getById($conf->general->logrecipient);
                    if($user instanceof User && $user->isAdmin()) {
                        $email = $user->getEmail();
                        if(!empty($email)){
                            $mail = Pimcore_Tool::getMail(array($email),"pimcore log notification");
                            $mail->setIgnoreDebugMode(true);
                            if(!is_dir(PIMCORE_LOG_MAIL_TEMP)){
                                mkdir(PIMCORE_LOG_MAIL_TEMP,0755,true);
                            }
                            $tempfile = PIMCORE_LOG_MAIL_TEMP."/log-".uniqid().".log";
                            $writerEmail = new Pimcore_Log_Writer_Mail($tempfile,$mail);
                            $loggerEmail = new Zend_Log($writerEmail);
                            Logger::addLogger($loggerEmail);
                        }
                    }
                }
            }
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
        @ini_set("memory_limit", "1024M");
        @ini_set("max_execution_time", $maxExecutionTime);
        @ini_set("short_open_tag", 1);
        @ini_set("magic_quotes_gpc", 0);
        @ini_set("magic_quotes_runtime", 0);
        set_time_limit($maxExecutionTime);
        mb_internal_encoding("UTF-8");

        // this is for simple_dom_html
        ini_set('pcre.recursion-limit', 100000);

        // check some system variables
        if (version_compare(PHP_VERSION, '5.3.0', "<")) {
            $m = "pimcore requires at least PHP version 5.3.0 your PHP version is: " . PHP_VERSION;
            die($m);
        }

        if (get_magic_quotes_gpc()) {
            $m = "pimcore requires magic_quotes_gpc OFF";
            die($m);
        }
    }

    /**
     * initialisze system modules and register them with the broker
     *
     * @static
     * @return void
     */
    public static function initModules() {

        $broker = Pimcore_API_Plugin_Broker::getInstance();
        $broker->registerModule("Search_Backend_Module");
    }

    public static function initPlugins() {
        // add plugin include paths

        $autoloader = Zend_Loader_Autoloader::getInstance();

        try {

            $pluginConfigs = Pimcore_ExtensionManager::getPluginConfigs();
            if (!empty($pluginConfigs)) {

                $includePaths = array(
                    get_include_path()
                );

                //adding plugin include paths and namespaces
                if (count($pluginConfigs) > 0) {
                    foreach ($pluginConfigs as $p) {

                        if(!Pimcore_ExtensionManager::isEnabled("plugin", $p["plugin"]["pluginName"])){
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

                $broker = Pimcore_API_Plugin_Broker::getInstance();

                //registering plugins
                foreach ($pluginConfigs as $p) {

                    if(!Pimcore_ExtensionManager::isEnabled("plugin", $p["plugin"]["pluginName"])){
                        continue;
                    }

                    $jsPaths = array();
                    if (is_array($p['plugin']['pluginJsPaths']['path'])) {
                        $jsPaths = $p['plugin']['pluginJsPaths']['path'];
                    }
                    else if ($p['plugin']['pluginJsPaths']['path'] != null) {
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
                    if (is_array($p['plugin']['pluginCssPaths']['path'])) {
                        $cssPaths = $p['plugin']['pluginCssPaths']['path'];
                    }
                    else if ($p['plugin']['pluginCssPaths']['path'] != null) {
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
                        if (!empty($className) && Pimcore_Tool::classExists($className)) {
                         
                            $plugin = new $className($jsPaths, $cssPaths);
                            if ($plugin instanceof Pimcore_API_Plugin_Abstract) {
                                $broker->registerPlugin($plugin);
                            }
                        }

                    } catch (Exception $e) {
                        Logger::err("Could not instantiate and register plugin [" . $p['plugin']['pluginClassName'] . "]");
                    }

                }
                Zend_Registry::set("Pimcore_API_Plugin_Broker", $broker);
            }
        }
        catch (Exception $e) {
            Logger::alert("there is a problem with the plugin configuration");
            Logger::alert($e);
        }

    }

    /**
     * @static
     *
     */
    public static function initAutoloader() {

        $autoloader = Zend_Loader_Autoloader::getInstance();

        $autoloader->registerNamespace('Logger');
        $autoloader->registerNamespace('Pimcore');
        $autoloader->registerNamespace('Document');
        $autoloader->registerNamespace('Object');
        $autoloader->registerNamespace('Asset');
        $autoloader->registerNamespace('User');
        $autoloader->registerNamespace('Property');
        $autoloader->registerNamespace('Version');
        $autoloader->registerNamespace('Sabre_');
        $autoloader->registerNamespace('Site');
        $autoloader->registerNamespace('Services_');
        $autoloader->registerNamespace('HTTP_');
        $autoloader->registerNamespace('Net_');
        $autoloader->registerNamespace('MIME_');
        $autoloader->registerNamespace('File_');
        $autoloader->registerNamespace('System_');
        $autoloader->registerNamespace('PEAR_');
        $autoloader->registerNamespace('Thumbnail');
        $autoloader->registerNamespace('Staticroute');
        $autoloader->registerNamespace('Redirect');
        $autoloader->registerNamespace('Dependency');
        $autoloader->registerNamespace('Schedule');
        $autoloader->registerNamespace('Translation');
        $autoloader->registerNamespace('Glossary');
        $autoloader->registerNamespace('Website');
        $autoloader->registerNamespace('Element');
        $autoloader->registerNamespace('API');
        $autoloader->registerNamespace('Minify');
        $autoloader->registerNamespace('Archive');
        $autoloader->registerNamespace('JSMin');
        $autoloader->registerNamespace('JSMinPlus');
        $autoloader->registerNamespace('Csv');
        $autoloader->registerNamespace('Webservice');
        $autoloader->registerNamespace('Search');
        $autoloader->registerNamespace('Tool');

        Pimcore_Tool::registerClassModelMappingNamespaces();
    }

    /**
     * @static
     * @return bool
     */
    public static function initConfiguration() {
               
        // init configuration
        try {
            $conf = Pimcore_Config::getSystemConfig();

            // set timezone
            if ($conf instanceof Zend_Config) {
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
            if (!empty($cacheLifetime)) {
                Pimcore_Model_Cache::addIgnoredTagOnClear("output");
            }

            return true;
        }
        catch (Exception $e) {
            $m = "Couldn't load system configuration";
            Logger::err($m);
            
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

        $conf = Pimcore_Config::getSystemConfig();
        $debug = (bool) $conf->general->debug;

        // enable debug mode only for one IP
        if($conf->general->debug_ip && $conf->general->debug) {
            $debug = false;
            if(Pimcore_Tool::getClientIp() == trim($conf->general->debug_ip)) {
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
        $pimcoreViewHelper = new Pimcore_Controller_Action_Helper_ViewRenderer();
        Zend_Controller_Action_HelperBroker::addHelper($pimcoreViewHelper);

        // set dummy timezone if no tz is specified / required for example by the logger, ...
        $defaultTimezone = @date_default_timezone_get();
        if(!$defaultTimezone) {
            date_default_timezone_set("Europe/Berlin");
        }
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
     * foreces a garbage collection
     * @static
     * @return void
     */
    public static function collectGarbage ($keepItems = array()) {

        // close mysql-connection
        Pimcore_Resource::close();

        $protectedItems = array(
            "Zend_Locale",
            "Zend_View_Helper_Placeholder_Registry",
            "Zend_View_Helper_Doctype",
            "Zend_Translate",
            "Zend_Navigation",
            "Pimcore_API_Plugin_Broker",
            "pimcore_tag_block_current",
            "pimcore_tag_block_numeration",
            "pimcore_user",
            "pimcore_config_system",
            "pimcore_admin_user",
            "pimcore_config_website",
            "pimcore_editmode",
            "pimcore_error_document",
            "pimcore_site"
        );

        if(is_array($keepItems) && count($keepItems) > 0) {
            $protectedItems = array_merge($protectedItems, $keepItems);
        }

        $registryBackup = array();

        foreach ($protectedItems as $item) {
            if(Zend_Registry::isRegistered($item)) {
                $registryBackup[$item] = Zend_Registry::get($item);
            }
        }

        Zend_Registry::_unsetInstance();

        foreach ($registryBackup as $key => $value) {
            Zend_Registry::set($key, $value);
        }

        Pimcore_Resource::reset();

        Logger::debug("garbage collection finished");
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
            ob_start("Pimcore::outputBufferEnd");
        }
    }

    /**
     * if this method is called in self::shutdown() it forces the browser to close the connection an allows the
     * shutdown-function to run in the background
     * @static
     * @return string
     */
    public static function outputBufferEnd ($data) {

        $contentEncoding = null;
        if( preg_match('@(?:^|,)\\s*((?:x-)?gzip)\\s*(?:$|,|;\\s*q=(?:0\\.|1))@' ,$_SERVER["HTTP_ACCEPT_ENCODING"] ,$m) ) {
            $contentEncoding = $m[1];
        }

        // only send this headers in the shutdown-function, so that it is also possible to get the contents of this buffer earlier without sending headers
        if(self::$inShutdown && !headers_sent() && !empty($data) && $contentEncoding) {
            ignore_user_abort(true);

            // find the content-type of the response
            $front = Zend_Controller_Front::getInstance();
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
            $gzipContentTypes = array("@text/html@i","@application/json@");
            $gzipIt = false;
            foreach ($gzipContentTypes as $type) {
                if(@preg_match($type, $contentType)) {
                    $gzipIt = true;
                    break;
                }
            }

            // gzip the contents and send connection close to that the process can run in the background to finish
            // some tasks like writing the cache ...
            // using mb_strlen() because of PIMCORE-1509
            if($gzipIt) {
                $output = "\x1f\x8b\x08\x00\x00\x00\x00\x00".
                    substr(gzcompress($data, 2), 0, -4).
                    pack('V', crc32($data)). // packing the CRC and the strlen is still required
                    pack('V', mb_strlen($data, "latin1")); // (although all modern browsers don't need it anymore) to work properly with google adwords check & co.

                // send headers & contents
                header("Connection: close\r\n");
                header("Content-Encoding: $contentEncoding\r\n");
                header("Content-Length: " . mb_strlen($output, "latin1"));
                header("X-Powered-By: pimcore");

                return $output;
            }
        }

        // return the data unchanged
        return $data;
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

        // clear tags scheduled for the shutdown
        Pimcore_Model_Cache::clearTagsOnShutdown();

        // write collected items to cache backend and remove the write lock
        Pimcore_Model_Cache::write();
        Pimcore_Model_Cache::removeWriteLock();

        // release all open locks from this process
        Tool_Lock::releaseAll();
    }

    /**
     * @static
     *
     */
    public static function shutdownHandler () {
        Pimcore_Event::fire("pimcore.shutdown");
    }
}

