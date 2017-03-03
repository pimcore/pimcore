<?php

use Pimcore\Config;
use Symfony\Component\Debug\Debug;

$debug = Pimcore::inDebugMode();
if ($debug && defined('PIMCORE_SYMFONY_MODE') && PIMCORE_SYMFONY_MODE) {
    Debug::enable();
}

$kernel = new AppKernel(Config::getEnvironment(), $debug);
Pimcore::setKernel($kernel);

return $kernel;
