<?php

use Pimcore\Config;
use Symfony\Component\Debug\Debug;

if (Pimcore::inDebugMode()) {
    Debug::enable();
}

$kernel = new AppKernel(Config::getEnvironment(), Pimcore::inDebugMode());
$kernel->boot();
Pimcore::setKernel($kernel);

return $kernel;
