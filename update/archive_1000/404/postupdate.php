<?php

    $configArray = Zend_Registry::get("pimcore_config_system")->toArray();
    $configArray["general"]["welcomescreen"] = "1";
    
    
    $config = new Zend_Config($configArray,true);
    $writer = new Zend_Config_Writer_Xml(array(
    	"config" => $config,
    	"filename" => PIMCORE_CONFIGURATION_SYSTEM
    ));
    $writer->write();
        
?>

<b>Release Notes (403):</b>
<br />
- Welcome Screen can generally be deactivated in sytem settings (by default it is activated)<br />
