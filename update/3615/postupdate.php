<?php

// create legacy config folder
$legacyFolder = PIMCORE_CONFIGURATION_DIRECTORY . "/LEGACY";
if(!is_dir($legacyFolder)) {
    mkdir($legacyFolder, 0777, true);
}

$extensionFile = \Pimcore\Config::locateConfigFile("extensions.xml");
if(file_exists($extensionFile)) {
    rename($extensionFile, $legacyFolder . "/" . basename($extensionFile));
}
