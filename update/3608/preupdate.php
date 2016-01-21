<?php

use Pimcore\Model\Asset;

// create legacy config folder
$legacyFolder = PIMCORE_CONFIGURATION_DIRECTORY . "/LEGACY";
if (!is_dir($legacyFolder)) {
    mkdir($legacyFolder, 0777, true);
}

// IMAGE THUMBNAILS
$dir = PIMCORE_CONFIGURATION_DIRECTORY . "/imagepipelines";

if(is_dir($dir)) {
    $file = Pimcore\Config::locateConfigFile("image-thumbnails");
    $json = \Pimcore\Db\JsonFileTable::get($file);
    $json->truncate();

    $files = scandir($dir);
    foreach ($files as $file) {
        if (strpos($file, ".xml")) {
            $name = str_replace(".xml", "", $file);
            $thumbnail = Asset\Image\Thumbnail\Config::getByName($name);
            $thumbnail = object2array($thumbnail);

            $thumbnail["id"] = $thumbnail["name"];
            unset($thumbnail["name"]);

            $json->insertOrUpdate($thumbnail, $thumbnail["id"]);
        }
    }

    // move data
    rename($dir, $legacyFolder . "/imagepipelines");
}


// VIDEO THUMBNAILS
$dir = PIMCORE_CONFIGURATION_DIRECTORY . "/videopipelines";

if(is_dir($dir)) {
    $file = Pimcore\Config::locateConfigFile("video-thumbnails");
    $json = \Pimcore\Db\JsonFileTable::get($file);
    $json->truncate();

    $files = scandir($dir);
    foreach ($files as $file) {
        if (strpos($file, ".xml")) {
            $name = str_replace(".xml", "", $file);
            $thumbnail = Asset\Video\Thumbnail\Config::getByName($name);
            $thumbnail = object2array($thumbnail);

            $thumbnail["id"] = $thumbnail["name"];
            unset($thumbnail["name"]);

            $json->insertOrUpdate($thumbnail, $thumbnail["id"]);
        }
    }

    // move data
    rename($dir, $legacyFolder . "/videopipelines");
}

