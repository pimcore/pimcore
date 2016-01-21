<?php

\Pimcore\Cache::disable();

// create legacy config folder
$legacyFolder = PIMCORE_CONFIGURATION_DIRECTORY . "/LEGACY";
if(!is_dir($legacyFolder)) {
    mkdir($legacyFolder, 0777, true);
}

$mappingFile = PIMCORE_CONFIGURATION_DIRECTORY . "/classmap.xml";

if(file_exists($mappingFile)) {
    try {
        $conf = new \Zend_Config_Xml($mappingFile);
        $arrayConf = $conf->toArray();

        $newConf = [];
        foreach($arrayConf as $key => $value) {
            $newKey = str_replace("_","\\", $key);
            $newConf[$newKey] = $value;
        }

        $content = json_encode($newConf);
        $content = \Zend_Json::prettyPrint($content);

        echo $content;

        $jsonFile = \Pimcore\Config::locateConfigFile("classmap.json");
        file_put_contents($jsonFile, $content);

        rename($mappingFile, $legacyFolder . "/classmap.xml");
    } catch (\Exception $e) {

    }
}
