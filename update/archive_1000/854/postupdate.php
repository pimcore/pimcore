<?php
$configArray = Zend_Registry::get("pimcore_config_system")->toArray();

$configArray["httpclient"] = array(
    "adapter" => "Zend_Http_Client_Adapter_Socket",
    "proxy_host" => "",
    "proxy_port" => "",
    "proxy_user" => "",
    "proxy_pass" => "",
);


$config = new Zend_Config($configArray,true);
$writer = new Zend_Config_Writer_Xml(array(
	"config" => $config,
	"filename" => PIMCORE_CONFIGURATION_SYSTEM
));
$writer->write();

?>