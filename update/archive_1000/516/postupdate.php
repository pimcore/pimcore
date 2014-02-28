<?php


// get db connection
$db = Pimcore_Resource_Mysql::get();
$db->getConnection()->exec("ALTER TABLE `staticroutes` ADD COLUMN `name` varchar(50) NULL DEFAULT NULL AFTER `id`;");
$db->getConnection()->exec("ALTER TABLE `staticroutes` ADD COLUMN `reverse` varchar(255) NULL DEFAULT NULL AFTER `pattern`;");
$db->getConnection()->exec("ALTER TABLE `staticroutes` ADD INDEX `name` (`name`(50));");

?>