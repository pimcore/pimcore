<?php

use Pimcore\Config;
use Symfony\Component\Debug\Debug;

$debug = Pimcore::inDebugMode();
if ($debug) {
    Debug::enable();
}

$kernel = new AppKernel(Config::getEnvironment(), $debug);
$kernel->loadClassCache();

Pimcore::setKernel($kernel);

return $kernel;
