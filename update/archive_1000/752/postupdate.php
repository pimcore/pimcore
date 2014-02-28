<?php


// add column to static routes
$db = Pimcore_Resource_Mysql::get("database");
$db->getConnection()->exec("ALTER TABLE `documents_doctypes` ADD COLUMN `priority` int(3) NOT NULL DEFAULT 0;");
$db->getConnection()->exec("ALTER TABLE `documents_doctypes` ADD INDEX `priority` (`priority`);");

?>