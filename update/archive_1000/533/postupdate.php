
<?php

    $configArray = Zend_Registry::get("pimcore_config_system")->toArray();

    if(!empty($configArray["cache"]["excludePatterns"])){
	$tmp = $configArray["cache"]["excludePatterns"];
	$tmp = str_replace("\r","",$tmp); 
	$excludePatterns = explode("\n",$tmp);
	$configArray["cache"]["excludePatterns"] = implode(",",$excludePatterns);
    }		

    if(!empty($configArray["outputfilters"]["cdnhostnames"])){
        $tmp = $configArray["outputfilters"]["cdnhostnames"];
        $tmp = str_replace("\r","",$tmp);
        $hostNames = explode("\n",$tmp);
        $configArray["outputfilters"]["cdnhostnames"] = implode(",",$hostNames);
    }

    if(!empty($configArray["outputfilters"]["cdnpatterns"])){
        $tmp = $configArray["outputfilters"]["cdnpatterns"];
        $tmp = str_replace("\r","",$tmp);
        $patterns = explode("\n",$tmp);
        $configArray["outputfilters"]["cdnpatterns"] = implode(",",$patterns);
    }


    $config = new Zend_Config($configArray,true);
    $writer = new Zend_Config_Writer_Xml(array(
        "config" => $config,
        "filename" => PIMCORE_CONFIGURATION_SYSTEM
    ));
    $writer->write();

?>


