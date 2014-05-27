<?php

// get db connection
$db = Pimcore_Resource::get();

try{

    $config = Pimcore_Config::getReportConfig()->toArray();

    if(isset($config["analytics"]) && is_array($config["analytics"]["sites"])) {
        foreach($config["analytics"]["sites"] as $siteKey => &$siteConfig) {
            if(!$siteConfig["universalcode"]) {
                $siteConfig["asynchronouscode"] = 1;
            }
        }
    }

    $config = new Zend_Config($config, true);
    $writer = new Zend_Config_Writer_Xml(array(
        "config" => $config,
        "filename" => PIMCORE_CONFIGURATION_DIRECTORY . "/reports.xml"
    ));
    $writer->write();

} catch (\Exception $e) {
    echo $e->getMessage();
}
