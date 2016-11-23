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


$configFile = \Pimcore\Config::locateConfigFile("system.php");

$systemSettings = include($configFile);
if(!isset($systemSettings["httpclient"]["adapter"]) || empty($systemSettings["httpclient"]["adapter"])) {
    $systemSettings["httpclient"]["adapter"] = "Zend_Http_Client_Adapter_Socket";
}

$contents = var_export_pretty($systemSettings);
$contents = "<?php \n\nreturn " . $contents . ";\n";
\Pimcore\File::put($configFile, $contents);
