<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();
$db->query("ALTER TABLE `keyvalue_groups` CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `id`;");
$db->query("ALTER TABLE `keyvalue_keys` CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `id`;");


