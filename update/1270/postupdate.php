<?php

$configArray = Pimcore_Config::getSystemConfig()->toArray();
$configArray["general"]["loginscreenimageservice"] = "1";

$config = new Zend_Config($configArray,true);
$writer = new Zend_Config_Writer_Xml(array(
	"config" => $config,
	"filename" => PIMCORE_CONFIGURATION_SYSTEM
));
$writer->write();

?>