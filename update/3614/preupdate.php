<?php

$configNames = ["document-types","image-thumbnails","newsletter", "predefined-asset-metadata", "custom-reports",
    "predefined-properties","qrcode","staticroutes","tag-manager","video-thumbnails","cache","classmap"];

foreach($configNames as $configName) {
    $jsonFile = \Pimcore\Config::locateConfigFile($configName . ".json");

    if(file_exists($jsonFile)) {
        $phpFile = \Pimcore\Config::locateConfigFile($configName . ".php");

        $contents = file_get_contents($jsonFile);
        $contents = json_decode($contents, true);
        $contents = var_export_pretty($contents);
        $phpContents = "<?php \n\nreturn " . $contents . ";";

        \Pimcore\File::put($phpFile, $phpContents);
    }
}

