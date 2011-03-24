<?php

// get db connection
$db = Pimcore_Resource_Mysql::get("database");

//drop old data table if exists and create new one
$db->exec("DROP TABLE IF EXISTS `plugin_searchphp_backend_data`;");
include_once(PIMCORE_PATH . "/modules/searchadmin/models/Search/Backend/Tool.php");
Search_Backend_Tool::createSearchDataTable();

//modify plugin to exclude backend search
if (is_file(PIMCORE_PLUGINS_PATH . "/SearchPhp/plugin.xml")) {

    $config = new Zend_Config_Xml(PIMCORE_PLUGINS_PATH . "/SearchPhp/plugin.xml");
    $configArray = $config->toArray();

    $configArray['plugin']['pluginJsPaths'] = array();
    $configArray['plugin']['pluginCssPaths'] = array();

    $config = new Zend_Config($configArray, true);
    $writer = new Zend_Config_Writer_Xml(array(
        "config" => $config,
        "filename" => PIMCORE_PLUGINS_PATH . "/SearchPhp/plugin.xml"
    ));
    $writer->write();
}

// update all Object_Class_Data_Input - fields because you can now specify the length of the field
$classList = new Object_Class_List();
$classes = $classList->load();
if(is_array($classes)){
    foreach($classes as $class){
        foreach ($class->getFieldDefinitions() as $fieldDef) {
            if($fieldDef instanceof Object_Class_Data_Input) {
                $fieldDef->setQueryColumnType("varchar");
                $fieldDef->setColumnType("varchar");
            }
            else if($fieldDef instanceof Object_Class_Data_Block) {
                foreach ($fieldDef->getChilds() as $child) {
                    if($child instanceof Object_Class_Data_Input) {
                        $child->setQueryColumnType("varchar");
                        $child->setColumnType("varchar");
                    }
                }
            }
        }
        $class->save();
    }
}


?>


<span style="color:red">IMPORTANT!</span><br/>
If you have installed the SearchPhp Plugin, please update this plugin to  the latest version now.
The backend search was moved to the core, without an update pimcore and the plugin might interfere with each other.