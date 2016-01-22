<?php

// create legacy config folder
$legacyFolder = PIMCORE_CONFIGURATION_DIRECTORY . "/LEGACY";
if(!is_dir($legacyFolder)) {
    mkdir($legacyFolder, 0777, true);
}

$configNames = ["document-types","image-thumbnails","newsletter", "predefined-asset-metadata", "custom-reports",
    "predefined-properties","qrcode","staticroutes","tag-manager","video-thumbnails"];

foreach($configNames as $configName) {
    $jsonFile = \Pimcore\Config::locateConfigFile($configName . ".json");
    if(file_exists($jsonFile)) {
        rename($jsonFile, $legacyFolder . "/" . basename($jsonFile));
    }
}
