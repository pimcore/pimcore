<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();
$db->query("ALTER TABLE `keyvalue_keys` CHANGE COLUMN `type` `type` ENUM('bool','number','select','text','translated') NULL DEFAULT NULL AFTER `description`;");

