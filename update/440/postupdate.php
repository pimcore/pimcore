<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->getConnection()->exec("ALTER TABLE `assets` CHANGE COLUMN `filename` `filename` varchar(255) NULL DEFAULT '';");
$db->getConnection()->exec("ALTER TABLE `documents` CHANGE COLUMN `key` `key` varchar(255) NULL DEFAULT '';");
$db->getConnection()->exec("ALTER TABLE `objects` CHANGE COLUMN `o_key` `o_key` varchar(255) NULL DEFAULT '';");

$db->update("assets",array("filename" => ""),"id = 1");
$db->update("documents",array("key" => ""),"id = 1");
$db->update("objects",array("o_key" => ""),"o_id = 1");

$db->getConnection()->exec("INSERT INTO `users_permission_definitions` SET `key`='reports',`translation`='permissions_reports_marketing';");

?>
Object grid-edit improvements 