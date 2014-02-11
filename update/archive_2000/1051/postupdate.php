<?php

$sql = "ALTER TABLE objects CHANGE o_type o_type ENUM('object', 'folder','variant'); ";
$sql .= "ALTER TABLE classes ADD allowVariants TINYINT(1) DEFAULT '0' AFTER propertyVisibility;";

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->exec($sql);

$classList = new Object_Class_List();
$classes = $classList->load();
if(is_array($classes)){
    foreach($classes as $class){
        $class->save();
    }
}

