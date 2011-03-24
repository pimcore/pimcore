<?php


// add column to static routes
$db = Pimcore_Resource_Mysql::get("database");
$db->getConnection()->exec("ALTER TABLE `properties` ADD COLUMN `inheritable` int(1) unsigned NOT NULL DEFAULT 1;");
$db->getConnection()->exec("ALTER TABLE `properties` ADD INDEX `inheritable` (`inheritable`);");
$db->getConnection()->exec("ALTER TABLE `properties` ADD INDEX `ctype` (`ctype`);");
$db->getConnection()->exec("ALTER TABLE `properties` ADD INDEX `cid` (`cid`);");


?>