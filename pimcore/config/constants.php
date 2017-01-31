<?php

// configure some constants needed by pimcore
$pimcoreDocumentRoot = realpath(dirname(__FILE__) . '/../..');

$customConstants = $pimcoreDocumentRoot . "/constants.php";
if (file_exists($customConstants)) {
    include_once $customConstants;
}


if (!defined("PIMCORE_DOCUMENT_ROOT")) {
    define("PIMCORE_DOCUMENT_ROOT", $pimcoreDocumentRoot);
}
// frontend module, this is the module containing your website, please be sure that the module folder is in PIMCORE_DOCUMENT_ROOT and is named identically with this name
if (!defined("PIMCORE_FRONTEND_MODULE")) {
    define("PIMCORE_FRONTEND_MODULE", "website");
}
if (!defined("PIMCORE_PATH")) {
    define("PIMCORE_PATH", PIMCORE_DOCUMENT_ROOT . "/pimcore");
}
if (!defined("PIMCORE_PLUGINS_PATH")) {
    define("PIMCORE_PLUGINS_PATH", PIMCORE_DOCUMENT_ROOT . "/plugins");
}

// website module specific
if (!defined("PIMCORE_WEBSITE_PATH")) {
    define("PIMCORE_WEBSITE_PATH", PIMCORE_DOCUMENT_ROOT . "/" . PIMCORE_FRONTEND_MODULE);
}


if (is_array($_SERVER)
    && array_key_exists("HTTP_X_PIMCORE_UNIT_TEST_REQUEST", $_SERVER)
    && in_array($_SERVER["REMOTE_ADDR"], ["127.0.0.1", $_SERVER["SERVER_ADDR"]])
) {
    // change the var directory for unit tests
    if (!defined("PIMCORE_WEBSITE_VAR")) {
        define("PIMCORE_WEBSITE_VAR", PIMCORE_DOCUMENT_ROOT . "/tests/tmp/var");
    }
} else {
    // use the default /website/var directory
    if (!defined("PIMCORE_WEBSITE_VAR")) {
        define("PIMCORE_WEBSITE_VAR", PIMCORE_WEBSITE_PATH . "/var");
    }
}

if (!defined("PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY")) {
    define("PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY", PIMCORE_WEBSITE_PATH . "/config");
}

if (!defined("PIMCORE_CONFIGURATION_DIRECTORY")) {
    define("PIMCORE_CONFIGURATION_DIRECTORY", PIMCORE_WEBSITE_VAR . "/config");
}
if (!defined("PIMCORE_ASSET_DIRECTORY")) {
    define("PIMCORE_ASSET_DIRECTORY", PIMCORE_WEBSITE_VAR . "/assets");
}
if (!defined("PIMCORE_VERSION_DIRECTORY")) {
    define("PIMCORE_VERSION_DIRECTORY", PIMCORE_WEBSITE_VAR . "/versions");
}
if (!defined("PIMCORE_WEBDAV_TEMP")) {
    define("PIMCORE_WEBDAV_TEMP", PIMCORE_WEBSITE_VAR . "/webdav");
}
if (!defined("PIMCORE_LOG_DIRECTORY")) {
    define("PIMCORE_LOG_DIRECTORY", PIMCORE_WEBSITE_VAR . "/log");
}
if (!defined("PIMCORE_LOG_DEBUG")) {
    define("PIMCORE_LOG_DEBUG", PIMCORE_LOG_DIRECTORY . "/debug.log");
}
if (!defined("PIMCORE_LOG_FILEOBJECT_DIRECTORY")) {
    define("PIMCORE_LOG_FILEOBJECT_DIRECTORY", PIMCORE_LOG_DIRECTORY . "/fileobjects");
}
if (!defined("PIMCORE_LOG_MAIL_TEMP")) {
    define("PIMCORE_LOG_MAIL_TEMP", PIMCORE_LOG_DIRECTORY . "/mail");
}
if (!defined("PIMCORE_TEMPORARY_DIRECTORY")) {
    define("PIMCORE_TEMPORARY_DIRECTORY", PIMCORE_WEBSITE_VAR . "/tmp");
}
if (!defined("PIMCORE_CACHE_DIRECTORY")) {
    define("PIMCORE_CACHE_DIRECTORY", PIMCORE_WEBSITE_VAR . "/cache");
}
if (!defined("PIMCORE_CLASS_DIRECTORY")) {
    define("PIMCORE_CLASS_DIRECTORY", PIMCORE_WEBSITE_VAR . "/classes");
}
if (!defined("PIMCORE_CUSTOMLAYOUT_DIRECTORY")) {
    define("PIMCORE_CUSTOMLAYOUT_DIRECTORY", PIMCORE_CLASS_DIRECTORY . "/customlayouts");
}
if (!defined("PIMCORE_BACKUP_DIRECTORY")) {
    define("PIMCORE_BACKUP_DIRECTORY", PIMCORE_WEBSITE_VAR . "/backup");
}
if (!defined("PIMCORE_RECYCLEBIN_DIRECTORY")) {
    define("PIMCORE_RECYCLEBIN_DIRECTORY", PIMCORE_WEBSITE_VAR . "/recyclebin");
}
if (!defined("PIMCORE_SYSTEM_TEMP_DIRECTORY")) {
    define("PIMCORE_SYSTEM_TEMP_DIRECTORY", PIMCORE_WEBSITE_VAR . "/system");
}
if (!defined("PIMCORE_LOG_MAIL_PERMANENT")) {
    define("PIMCORE_LOG_MAIL_PERMANENT", PIMCORE_WEBSITE_VAR . "/email");
}

if (!defined('PIMCORE_SYMFONY_APP')) {
    define("PIMCORE_SYMFONY_APP", PIMCORE_DOCUMENT_ROOT . '/app');
}

if (!defined('PIMCORE_SYMFONY_MODE')) {
    define('PIMCORE_SYMFONY_MODE', false);
}
