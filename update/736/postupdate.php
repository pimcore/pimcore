<?php

$configArray = Zend_Registry::get("pimcore_config_system")->toArray();
if(!is_array($configArray["general"]["loglevel"])){
    $configArray["general"]["loglevel"]["debug"]=0;
    $configArray["general"]["loglevel"]["info"]=0;
    $configArray["general"]["loglevel"]["notice"]=0;
    $configArray["general"]["loglevel"]["warning"]=0;
    $configArray["general"]["loglevel"]["error"]=0;
}
$configArray["general"]["loglevel"]["emergency"]=1;
$configArray["general"]["loglevel"]["critical"]=1;
$configArray["general"]["loglevel"]["alert"]=1;

$config = new Zend_Config($configArray,true);
$writer = new Zend_Config_Writer_Xml(array(
        "config" => $config,
        "filename" => PIMCORE_CONFIGURATION_SYSTEM
));
$writer->write();

