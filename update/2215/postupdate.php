<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->exec("ALTER TABLE `users` CHANGE COLUMN `language` `language` varchar(10) NULL DEFAULT NULL;");
$db->exec("ALTER TABLE `glossary` CHANGE COLUMN `language` `language` varchar(10) NULL DEFAULT NULL;");

