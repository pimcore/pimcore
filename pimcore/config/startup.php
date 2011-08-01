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

error_reporting(E_ALL ^ E_NOTICE);

// configure some constants needed by pimcore
$pimcoreDocumentRoot = realpath(dirname(__FILE__) . '/../..'); 

if (!defined("PIMCORE_DOCUMENT_ROOT"))  define("PIMCORE_DOCUMENT_ROOT", $pimcoreDocumentRoot);
if (!defined("PIMCORE_FRONTEND_MODULE"))  define("PIMCORE_FRONTEND_MODULE", "website"); // frontend module, this is the module containing your website, please be sure that the module folder is in PIMCORE_DOCUMENT_ROOT and is named identically with this name
if (!defined("PIMCORE_PATH"))  define("PIMCORE_PATH", PIMCORE_DOCUMENT_ROOT . "/pimcore");
if (!defined("PIMCORE_PLUGINS_PATH"))  define("PIMCORE_PLUGINS_PATH", PIMCORE_DOCUMENT_ROOT . "/plugins");

// website module specific
if (!defined("PIMCORE_WEBSITE_PATH"))  define("PIMCORE_WEBSITE_PATH", PIMCORE_DOCUMENT_ROOT . "/" . PIMCORE_FRONTEND_MODULE);
if (!defined("PIMCORE_CONFIGURATION_DIRECTORY"))  define("PIMCORE_CONFIGURATION_DIRECTORY", PIMCORE_WEBSITE_PATH . "/var/config");
if (!defined("PIMCORE_CONFIGURATION_SYSTEM"))  define("PIMCORE_CONFIGURATION_SYSTEM", PIMCORE_CONFIGURATION_DIRECTORY . "/system.xml");
if (!defined("PIMCORE_CONFIGURATION_PLUGINS"))  define("PIMCORE_CONFIGURATION_PLUGINS", PIMCORE_CONFIGURATION_DIRECTORY . "/plugin.xml");
if (!defined("PIMCORE_ASSET_DIRECTORY"))  define("PIMCORE_ASSET_DIRECTORY", PIMCORE_WEBSITE_PATH . "/var/assets");
if (!defined("PIMCORE_VERSION_DIRECTORY"))  define("PIMCORE_VERSION_DIRECTORY", PIMCORE_WEBSITE_PATH . "/var/versions");
if (!defined("PIMCORE_WEBDAV_TEMP"))  define("PIMCORE_WEBDAV_TEMP", PIMCORE_WEBSITE_PATH . "/var/webdav");
if (!defined("PIMCORE_LOG_DEBUG"))  define("PIMCORE_LOG_DEBUG", PIMCORE_WEBSITE_PATH . "/var/log/debug.log");
if (!defined("PIMCORE_LOG_MAIL_TEMP"))  define("PIMCORE_LOG_MAIL_TEMP", PIMCORE_WEBSITE_PATH . "/var/log/mail");
if (!defined("PIMCORE_TEMPORARY_DIRECTORY"))  define("PIMCORE_TEMPORARY_DIRECTORY", PIMCORE_WEBSITE_PATH . "/var/tmp");
if (!defined("PIMCORE_CACHE_DIRECTORY"))  define("PIMCORE_CACHE_DIRECTORY", PIMCORE_WEBSITE_PATH . "/var/cache");
if (!defined("PIMCORE_CLASS_DIRECTORY"))  define("PIMCORE_CLASS_DIRECTORY", PIMCORE_WEBSITE_PATH . "/var/classes");
if (!defined("PIMCORE_BACKUP_DIRECTORY"))  define("PIMCORE_BACKUP_DIRECTORY", PIMCORE_WEBSITE_PATH . "/var/backup");
if (!defined("PIMCORE_RECYCLEBIN_DIRECTORY"))  define("PIMCORE_RECYCLEBIN_DIRECTORY", PIMCORE_WEBSITE_PATH . "/var/recyclebin");
if (!defined("PIMCORE_SYSTEM_TEMP_DIRECTORY"))  define("PIMCORE_SYSTEM_TEMP_DIRECTORY", PIMCORE_WEBSITE_PATH . "/var/system");

// setup include pathes
$includePaths = array(
    PIMCORE_PATH . "/lib",
    PIMCORE_PATH . "/models",
    PIMCORE_WEBSITE_PATH . "/lib",
    PIMCORE_WEBSITE_PATH . "/models",
    PIMCORE_PATH . "/modules/searchadmin/models",
    PIMCORE_CLASS_DIRECTORY,
    PIMCORE_PATH . "/lib/_deprecated",  // depricated libraries and classes
    get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $includePaths));

// helper functions
include(dirname(__FILE__) . "/helper.php");

// setup zend framework and pimcore
require_once "Pimcore.php";
require_once "Logger.php";
require_once "Zend/Loader.php";
require_once "Zend/Loader/Autoloader.php";

$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Pimcore');

$autoloader->suppressNotFoundWarnings(true);


// do some general stuff

// on pimcore shutdown
register_shutdown_function("Pimcore::shutdown");

