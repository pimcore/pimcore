<?php

\Pimcore\Cache::disable();

$customCacheFile = PIMCORE_CONFIGURATION_DIRECTORY . "/cache.xml";
// create legacy config folder
$legacyFolder = PIMCORE_CONFIGURATION_DIRECTORY . "/LEGACY";
if(!is_dir($legacyFolder)) {
    mkdir($legacyFolder, 0777, true);
}


if(file_exists($customCacheFile)) {
    try {
        $conf = new \Zend_Config_Xml($customCacheFile);
        $arrayConf = $conf->toArray();

        $content = json_encode($arrayConf);
        $content = \Zend_Json::prettyPrint($content);

        $jsonFile = \Pimcore\Config::locateConfigFile("cache.json");
        file_put_contents($jsonFile, $content);

        rename($customCacheFile, $legacyFolder . "/cache.xml");
    } catch (\Exception $e) {

    }
}
