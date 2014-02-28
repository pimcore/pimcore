<?php
$db = Pimcore_Resource_Mysql::get("database");

$db->exec("ALTER TABLE `assets` ADD COLUMN `locked` enum('self','propagate') NULL DEFAULT NULL;");
$db->exec("ALTER TABLE `documents` ADD COLUMN `locked` enum('self','propagate') NULL DEFAULT NULL;");
$db->exec("ALTER TABLE `objects` ADD COLUMN `o_locked` enum('self','propagate') NULL DEFAULT NULL;");


$db->exec("ALTER TABLE `assets` ADD INDEX `locked` (`locked`);");
$db->exec("ALTER TABLE `documents` ADD INDEX `locked` (`locked`);");
$db->exec("ALTER TABLE `objects` ADD INDEX `o_locked` (`o_locked`);");


