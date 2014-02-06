<?php

// valid languages is new in system config
$configArray = Pimcore_Config::getSystemConfig()->toArray();
$configArray["general"]["debugloglevel"] = "error";
$configArray = array_htmlspecialchars($configArray);

$config = new Zend_Config($configArray,true);
$writer = new Zend_Config_Writer_Xml(array(
    "config" => $config,
    "filename" => PIMCORE_CONFIGURATION_SYSTEM
));
$writer->write();

