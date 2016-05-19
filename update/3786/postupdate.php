<?php

$db = \Pimcore\Db::get();

// REDIRECTS: added active flag to enable/disable them
$db->query("ALTER TABLE `redirects` ADD COLUMN `active` tinyint(1) NULL DEFAULT NULL AFTER `priority`;");
$db->update("redirects", ["active" => 1]); // set all existing redirects to active


// no more individual configuration paths for executables
$config = \Pimcore\Config::getSystemConfig();
$existingConfigArray = $config->toArray();
$paths = [];

foreach(["ffmpeg","ghostscript","libreoffice","pngcrush","imgmin","jpegoptim","pdftotext"] as $app) {
    if(isset($config->assets->$app)) {
        $pathOnly = dirname($config->assets->$app);
        if(strlen($pathOnly) > 1) {
            $paths[] = $pathOnly;
        }

        unset($existingConfigArray["assets"][$app]);
    }
}

if(isset($config->general->php_cli)) {
    $pathOnly = dirname($config->general->php_cli);
    if(strlen($pathOnly) > 1) {
        $paths[] = $pathOnly;
    }

    unset($existingConfigArray["general"]["php_cli"]);
}

$newPath = implode(":",$paths);
$existingConfigArray["general"]["path_variable"] = $newPath;

$configFile = \Pimcore\Config::locateConfigFile("system.php");
\Pimcore\File::putPhpFile($configFile, to_php_data_file_format($existingConfigArray));

