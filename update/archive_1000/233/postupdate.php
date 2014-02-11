<?php

    $configArray = Zend_Registry::get("pimcore_config_system")->toArray();
    $configArray["services"]["googlemaps"]["apikey"] = "";
    
    
    $config = new Zend_Config($configArray,true);
    $writer = new Zend_Config_Writer_Xml(array(
    	"config" => $config,
    	"filename" => PIMCORE_CONFIGURATION_SYSTEM
    ));
    $writer->write();
        
?>

<b>Release Notes (233):</b>
<br />
- GeoPoint for objects with OSM (standard) or Google Maps (if key configured) preview<br />
