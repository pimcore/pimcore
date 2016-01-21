<?php

// QR-CODES
$dir = PIMCORE_CONFIGURATION_DIRECTORY . "/qrcodes";

$file = Pimcore\Config::locateConfigFile("qrcode.json");
$json = \Pimcore\Db\JsonFileTable::get($file);
$json->truncate();

$files = scandir($dir);
foreach ($files as $file) {
    if(strpos($file, ".xml")) {
        $name = str_replace(".xml", "", $file);
        $thumbnail = \Pimcore\Model\Tool\Qrcode\Config::getByName($name);
        $thumbnail = object2array($thumbnail);

        $thumbnail["id"] = $thumbnail["name"];
        unset($thumbnail["name"]);

        $json->insertOrUpdate($thumbnail, $thumbnail["id"]);
    }
}

// create legacy config folder
$legacyFolder = PIMCORE_CONFIGURATION_DIRECTORY . "/LEGACY";
if(!is_dir($legacyFolder)) {
    mkdir($legacyFolder, 0777, true);
}

// move data
rename($dir, $legacyFolder . "/qrcodes");

