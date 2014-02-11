<?php


// add column to static routes
$db = Pimcore_Resource_Mysql::get("database");
$db->getConnection()->exec("ALTER TABLE `documents_elements` CHANGE COLUMN `name` `name` varchar(255) NOT NULL DEFAULT '';");

?>