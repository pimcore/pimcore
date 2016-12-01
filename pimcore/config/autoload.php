<?php

// setup include paths
// include paths defined in php.ini are ignored because they're causing problems with open_basedir, see PIMCORE-1233
// it also improves the performance when reducing the amount of include paths, you can of course add additional paths anywhere in your code (/website)
$includePaths = [
    PIMCORE_PATH . "/lib",
    PIMCORE_PATH . "/models",
    PIMCORE_WEBSITE_PATH . "/lib",
    PIMCORE_WEBSITE_PATH . "/models",
    PIMCORE_CLASS_DIRECTORY,
    // we need to include the path to the ZF1, because we cannot remove all require_once() out of the source
    // see also: Pimcore\Composer::zendFrameworkOptimization()
    // actually the problem is 'require_once 'Zend/Loader.php';' in Zend/Loader/Autoloader.php
    PIMCORE_DOCUMENT_ROOT . "/vendor/zendframework/zendframework1/library/",
];
set_include_path(implode(PATH_SEPARATOR, $includePaths) . PATH_SEPARATOR);

// composer autoloader
$composerLoader = include_once(PIMCORE_DOCUMENT_ROOT . "/vendor/autoload.php");

// helper functions
include(dirname(__FILE__) . "/helper.php");

// setup zend framework and pimcore
require_once PIMCORE_PATH . "/lib/Pimcore.php";
require_once PIMCORE_PATH . "/lib/Pimcore/Logger.php";

$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->suppressNotFoundWarnings(false);
$autoloader->setFallbackAutoloader(false);
$autoloader->registerNamespace('Pimcore');

// register class map loader => speed
$autoloaderClassMapFiles = [
    PIMCORE_CONFIGURATION_DIRECTORY . "/autoload-classmap.php",
    PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/autoload-classmap.php",
    PIMCORE_PATH . "/config/autoload-classmap.php",
];

foreach ($autoloaderClassMapFiles as $autoloaderClassMapFile) {
    if (file_exists($autoloaderClassMapFile)) {
        $classMapAutoLoader = new \Pimcore\Loader\ClassMapAutoloader([$autoloaderClassMapFile]);
        $classMapAutoLoader->register();
        break;
    }
}

return $composerLoader;
