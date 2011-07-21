<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();


try {

    $db->exec("ALTER TABLE `documents_doctypes` CHANGE COLUMN `priority` `priority` int(3) NULL DEFAULT 0;");
    $db->exec("ALTER TABLE `properties_predefined` CHANGE COLUMN `inheritable` `inheritable` tinyint(1) unsigned NULL DEFAULT 0;");
    $db->exec("ALTER TABLE `properties_predefined` CHANGE COLUMN `name` `name` varchar(255) NULL DEFAULT '';");
    $db->exec("ALTER TABLE `staticroutes` CHANGE COLUMN `priority` `priority` int(3) NULL DEFAULT 0;");

    // update all classes to be mysql strict mode compatible
    $classList = new Object_Class_List();
    $classes = $classList->load();
    if(is_array($classes)){
        foreach($classes as $class){
            $class->save();
        }
    }

} catch (Exception $e) {
    echo $e->getMessage();
}
    
