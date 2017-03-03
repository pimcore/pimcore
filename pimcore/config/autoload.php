<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

// setup include paths
// include paths defined in php.ini are ignored because they're causing problems with open_basedir, see PIMCORE-1233
// it also improves the performance when reducing the amount of include paths, you can of course add additional paths anywhere in your code (/website)
$includePaths = [
    PIMCORE_PATH . "/lib",
    PIMCORE_PATH . "/models",
    PIMCORE_CLASS_DIRECTORY,
    // we need to include the path to the ZF1, because we cannot remove all require_once() out of the source
    // see also: Pimcore\Composer::zendFrameworkOptimization()
    // actually the problem is 'require_once 'Zend/Loader.php';' in Zend/Loader/Autoloader.php
    PIMCORE_PROJECT_ROOT . "/vendor/zendframework/zendframework1/library/",
];
set_include_path(implode(PATH_SEPARATOR, $includePaths) . PATH_SEPARATOR);

// composer autoloader
if (PIMCORE_SYMFONY_MODE) {
    $composerLoader = require_once PIMCORE_APP_ROOT . '/autoload.php';
} else {
    $composerLoader = require_once PIMCORE_PROJECT_ROOT . '/vendor/autoload.php';
}

// helper functions
include(dirname(__FILE__) . "/helper.php");

// setup zend framework and pimcore
require_once PIMCORE_PATH . "/lib/Pimcore.php";
require_once PIMCORE_PATH . "/lib/Pimcore/Logger.php";

if(!class_exists("Zend_Date")) {
    // if ZF is not loaded, we need to provide some compatibility stubs
    // for a detailed description see the included file
    require_once PIMCORE_PATH . "/lib/compatibility-stubs.php";
}

return $composerLoader;
