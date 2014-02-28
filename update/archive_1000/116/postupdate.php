<?php

    $configArray = Zend_Registry::get("pimcore_config_system")->toArray();
    $configArray["outputfilters"] = array(
        "imagedatauri" => ""
    );
    
    
    $config = new Zend_Config($configArray,true);
    $writer = new Zend_Config_Writer_Xml(array(
    	"config" => $config,
    	"filename" => PIMCORE_CONFIGURATION_SYSTEM
    ));
    $writer->write();
        
?>