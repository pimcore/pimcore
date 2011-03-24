<?php

// get db connection
$db = Pimcore_Resource_Mysql::get("database");

$db->getConnection()->exec("ALTER TABLE `properties_predefined` ADD COLUMN `inheritable` tinyint(1) unsigned NOT NULL DEFAULT 0;");


?>