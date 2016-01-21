<?php

// create legacy config folder
$legacyFolder = PIMCORE_CONFIGURATION_DIRECTORY . "/LEGACY";
if (!is_dir($legacyFolder)) {
    mkdir($legacyFolder, 0777, true);
}

// NEWSLETTER
$dir = PIMCORE_CONFIGURATION_DIRECTORY . "/newsletter";

if(is_dir($dir)) {
    $file = Pimcore\Config::locateConfigFile("newsletter.json");
    $json = \Pimcore\Db\JsonFileTable::get($file);
    $json->truncate();

    $files = scandir($dir);
    foreach ($files as $file) {
        if (strpos($file, ".xml")) {
            $name = str_replace(".xml", "", $file);
            $thumbnail = \Pimcore\Model\Tool\Newsletter\Config::getByName($name);
            $thumbnail = object2array($thumbnail);

            $thumbnail["id"] = $thumbnail["name"];
            unset($thumbnail["name"]);

            $json->insertOrUpdate($thumbnail, $thumbnail["id"]);
        }
    }

    // move data
    rename($dir, $legacyFolder . "/newsletter");
}


// TAG SNIPPET MANAGEMENT
$dir = PIMCORE_CONFIGURATION_DIRECTORY . "/tags";

if(is_dir($dir)) {
    $file = Pimcore\Config::locateConfigFile("tag-manager.json");
    $json = \Pimcore\Db\JsonFileTable::get($file);
    $json->truncate();

    $files = scandir($dir);
    foreach ($files as $file) {
        if (strpos($file, ".xml")) {
            $name = str_replace(".xml", "", $file);
            $thumbnail = Pimcore\Model\Tool\Tag\Config::getByName($name);
            $thumbnail = object2array($thumbnail);

            $thumbnail["id"] = $thumbnail["name"];
            unset($thumbnail["name"]);

            $json->insertOrUpdate($thumbnail, $thumbnail["id"]);
        }
    }

    // move data
    rename($dir, $legacyFolder . "/tags");
}
