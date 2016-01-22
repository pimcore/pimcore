<?php

$extensionFile = \Pimcore\Config::locateConfigFile("extensions.xml");

if(file_exists($extensionFile)) {
    $phpFile = \Pimcore\Config::locateConfigFile("extensions.php");

    $config = new \Zend_Config_Xml(PIMCORE_CONFIGURATION_DIRECTORY . "/extensions.xml", null, array("allowModifications" => true));
    $contents = $config->toArray();

    $contents = var_export_pretty($contents);
    $phpContents = "<?php \n\nreturn " . $contents . ";\n";

    \Pimcore\File::put($phpFile, $phpContents);
}
