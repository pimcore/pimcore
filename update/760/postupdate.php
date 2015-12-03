<?php

// get db connection
$db = Pimcore_Resource_Mysql::get("database");
$db->getConnection()->exec("INSERT INTO  `users` (parentId,username,admin,hasCredentials,active) values (0,'system',1,1,1);");
$db->getConnection()->exec("UPDATE  `users` SET id = 0 WHERE username = 'system'");

$db->getConnection()->exec("CREATE TABLE `sanitycheck` (
  `id` int(11) unsigned NOT NULL,
  `type` enum('document','asset','object') NOT NULL,
  PRIMARY KEY  (`id`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");


//since Pimcore_Tool_Element was removed ... save all object classes
$classList = new Object_Class_List();
$classes = $classList->load();
if(is_array($classes)){
    foreach($classes as $class){
        $class->save();
    }
}
