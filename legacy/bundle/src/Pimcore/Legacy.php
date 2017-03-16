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

use Composer\Autoload\ClassLoader;
use Pimcore\Legacy\EventManager;

class Legacy {

    /**
     * @var \Zend_EventManager_EventManager
     */
    private static $eventManager;

    /**
     * @var bool
     */
    private static $mvcPrepared = false;

    /**
     * @static

     * @param bool $returnResponse
     * @param \Zend_Controller_Request_Abstract $request
     * @param \Zend_Controller_Response_Abstract $response
     * @return null|\Zend_Controller_Response_Http
     * @throws \Exception
     * @throws \Zend_Controller_Router_Exception
     */
    public static function run($returnResponse = false, \Zend_Controller_Request_Abstract $request = null, \Zend_Controller_Response_Abstract $response = null)
    {
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
     * @return \Zend_Controller_Front
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
     * Force the main (default) domain for "admin" requests
     *
     * @param \Pimcore\Config\Config $conf
     */
    protected static function handleAdminMainDomainRedirect(\Pimcore\Config\Config $conf)
    {
        if ($conf->general->domain && $conf->general->domain != Tool::getHostname()) {
            $url = (($_SERVER['HTTPS'] == "on") ? "https" : "http") . "://" . $conf->general->domain . $_SERVER["REQUEST_URI"];
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $url, true, 301);
            exit;
        }
    }

    /**
     * Check if this request routes into a plugin, if so check if the plugin is enabled
     */
    protected static function checkPluginRoutes()
    {
        if (preg_match("@^/plugin/([^/]+)/.*@", $_SERVER["REQUEST_URI"], $matches)) {
            $pluginName = $matches[1];
            if (!\Pimcore\ExtensionManager::isEnabled("plugin", $pluginName)) {
                \Pimcore\Tool::exitWithError("Plugin is disabled. To use this plugin please enable it in the extension manager!");
            }
        }
    }

    /**
     * Determine if MVC stack should throw exceptions
     *
     * @param \Pimcore\Config\Config|mixed|null $conf
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
            if ($user instanceof \Pimcore\Model\User) {
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
     * @param \Zend_Controller_Front $front
     * @param bool $throwExceptions
     * @param \Zend_Controller_Request_Abstract|null $request
     * @param \Zend_Controller_Response_Abstract|null $response
     * @return null|\Zend_Controller_Response_Abstract
     * @throws \Exception
     * @throws \Zend_Controller_Router_Exception
     */
    protected static function runDispatcher(
        \Zend_Controller_Front $front,
        $throwExceptions,
        \Zend_Controller_Request_Abstract $request = null,
        \Zend_Controller_Response_Abstract $response = null
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
     * Register front controller plugins
     *
     * @param \Zend_Controller_Front $front
     * @param bool $frontend
     */
    protected static function registerFrontControllerPlugins(\Zend_Controller_Front $front, $frontend)
    {
        $front->registerPlugin(new Controller\Plugin\ErrorHandler(), 1);

        if (Tool::useFrontendOutputFilters(new \Zend_Controller_Request_Http())) {
            $front->registerPlugin(new Controller\Plugin\HttpErrorLog(), 850);
        }
    }

    /**
     * Add global routes
     *
     * @param \Zend_Controller_Front $front
     * @return \Zend_Controller_Router_Interface|\Zend_Controller_Router_Rewrite
     */
    protected static function initRouter(\Zend_Controller_Front $front)
    {
        /** @var \Zend_Controller_Router_Interface|\Zend_Controller_Router_Rewrite $router */
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
     * @param \Zend_Controller_Router_Interface|\Zend_Controller_Router_Rewrite $router
     * @param \Pimcore\Config\Config|null $conf
     */
    protected static function initBackendRouter(\Zend_Controller_Router_Interface $router, $conf)
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

        if ($conf instanceof \Pimcore\Config\Config and $conf->webservice and $conf->webservice->enabled) {
            $router->addRoute('webservice', $routeWebservice);
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

        /** @var ClassLoader $autoloader */
        $autoloader = require PIMCORE_COMPOSER_PATH . '/autoload.php';

        try {
            $pluginConfigs = ExtensionManager::getPluginConfigs();
            if (!empty($pluginConfigs)) {
                //adding plugin include paths and namespaces
                if (count($pluginConfigs) > 0) {
                    foreach ($pluginConfigs as $p) {
                        if (!ExtensionManager::isEnabled("plugin", $p["plugin"]["pluginName"])) {
                            continue;
                        }

                        $namespaces   = [];
                        $includePaths = [];

                        if (is_array($p['plugin']['pluginIncludePaths']['path'])) {
                            foreach ($p['plugin']['pluginIncludePaths']['path'] as $path) {
                                $includePaths[] = PIMCORE_PLUGINS_PATH . $path;
                            }
                        } elseif ($p['plugin']['pluginIncludePaths']['path'] != null) {
                            $includePaths[] = PIMCORE_PLUGINS_PATH . $p['plugin']['pluginIncludePaths']['path'];
                        }
                        if (is_array($p['plugin']['pluginNamespaces']['namespace'])) {
                            foreach ($p['plugin']['pluginNamespaces']['namespace'] as $namespace) {
                                $namespaces[] = $namespace;
                            }
                        } elseif ($p['plugin']['pluginNamespaces']['namespace'] != null) {
                            $namespaces[] = $p['plugin']['pluginNamespaces']['namespace'];
                        }

                        // add path without prefix
                        // TODO namespaces are ignored for now as the PSR-0 loader with empty prefix should take care of
                        // all namespaces in the include path - can we gain performance by additionally adding namespaces?
                        $autoloader->add('', $includePaths);
                    }

                }

                /** @var \Pimcore\API\Plugin\Broker $broker */
                $broker = \Pimcore::getContainer()->get('pimcore.plugin_broker');

                //registering plugins
                foreach ($pluginConfigs as $p) {
                    if (!ExtensionManager::isEnabled("plugin", $p["plugin"]["pluginName"])) {
                        continue;
                    }

                    $jsPaths  = ExtensionManager::getAssetPaths('js', false, $p["plugin"]["pluginName"]);
                    $cssPaths = ExtensionManager::getAssetPaths('css', false, $p["plugin"]["pluginName"]);

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
     */
    public static function setupZendViewRenderer()
    {
        // set custom view renderer
        $pimcoreViewHelper = new Controller\Action\Helper\ViewRenderer();
        \Zend_Controller_Action_HelperBroker::addHelper($pimcoreViewHelper);
    }

    /**
     * @param array $keepItems
     */
    public static function collectGarbage($keepItems = []) {

        $registryBackup = [];
        $keepItems = array_merge($keepItems, [
            "Zend_Locale",
            "Zend_View_Helper_Placeholder_Registry",
            "Zend_View_Helper_Doctype",
            "Zend_Translate",
            "Zend_Navigation",
        ]);

        foreach ($keepItems as $item) {
            if (\Zend_Registry::isRegistered($item)) {
                $registryBackup[$item] = \Zend_Registry::get($item);
            }
        }

        \Zend_Registry::_unsetInstance();

        foreach ($registryBackup as $key => $value) {
            \Zend_Registry::set($key, $value);
        }
    }

    /**
     * @return \Zend_EventManager_EventManager
     */
    public static function getEventManager()
    {
        if (!self::$eventManager) {
            self::$eventManager = new EventManager();
        }

        return self::$eventManager;
    }
}
