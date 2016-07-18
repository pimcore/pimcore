<?php

// create legacy config folder
$legacyFolder = PIMCORE_CONFIGURATION_DIRECTORY . "/LEGACY";
if(!is_dir($legacyFolder)) {
    mkdir($legacyFolder, 0777, true);
}

$files = ["extensions","customviews","reports","system"];

foreach($files as $fileName) {
    $xmlFile = \Pimcore\Config::locateConfigFile($fileName . ".xml");

    if (file_exists($xmlFile)) {
        rename($xmlFile, $legacyFolder . "/" . basename($xmlFile));
    }
}
