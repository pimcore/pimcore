<?php

// generic pimcore startup
\Pimcore::setSystemRequirements();
\Pimcore::initAutoloader();
\Pimcore::initConfiguration();
\Pimcore::setupTempDirectories();
\Pimcore::setupZendViewRenderer();
\Pimcore::initLogger();

if (\Pimcore\Config::getSystemConfig()) {
    // we do not initialize plugins if pimcore isn't installed properly
    // reason: it can be the case that plugins use the database in isInstalled() witch isn't available at this time
    \Pimcore::initPlugins();
}

// do some general stuff
// this is just for compatibility reasons, pimcore itself doesn't use this constant anymore
if (!defined("PIMCORE_CONFIGURATION_SYSTEM")) {
    define("PIMCORE_CONFIGURATION_SYSTEM", \Pimcore\Config::locateConfigFile("system.php"));
}

$websiteStartup = \Pimcore\Config::locateConfigFile("startup.php");
if (@is_file($websiteStartup)) {
    include_once($websiteStartup);
}

// on pimcore shutdown
register_shutdown_function(function () {
    \Pimcore::getEventManager()->trigger("system.shutdown");
});

include_once("event-listeners.php");
