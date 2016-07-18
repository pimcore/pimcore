<?php

$sourceFile = PIMCORE_CONFIGURATION_DIRECTORY . "/customviews.php";
$destinationDir = PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY;

$destinationFile = $destinationDir . "/customviews.php";

if (is_file($sourceFile) && !is_file($destinationFile) && is_writable(PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY)) {
    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0777, true);
    }

    copy($sourceFile, $destinationFile);


    // create legacy config folder
    $legacyFolder = PIMCORE_CONFIGURATION_DIRECTORY . "/LEGACY";
    if (!is_dir($legacyFolder)) {
        mkdir($legacyFolder, 0777, true);
    }
    // rename source file
    $archiveFile = $legacyFolder . "/customviews.php";
    rename($sourceFile, $archiveFile);
}
\Pimcore\Cache::clearAll();
