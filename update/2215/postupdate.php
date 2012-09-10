<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->query("ALTER TABLE `users` CHANGE COLUMN `language` `language` varchar(10) NULL DEFAULT NULL;");
$db->query("ALTER TABLE `glossary` CHANGE COLUMN `language` `language` varchar(10) NULL DEFAULT NULL;");

