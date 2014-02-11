<?php
// email config is new in system settings
$configArray = Zend_Registry::get("pimcore_config_system")->toArray();
$configArray["email"] = array(
    "sender"=> array("name"=>"","email"=>""),
    "return"=> array("name"=>"","email"=>""),
    "method"=>"sendmail",
    "smtp"=> array("host"=>"","port"=>"","name"=>"","auth"=>array("method"=>"","username"=>"","password"=>"")),
);


$config = new Zend_Config($configArray,true);
$writer = new Zend_Config_Writer_Xml(array(
	"config" => $config,
	"filename" => PIMCORE_CONFIGURATION_SYSTEM
));
$writer->write();