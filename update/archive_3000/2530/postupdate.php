<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();
$db->query("ALTER TABLE `keyvalue_keys` ADD COLUMN `translator` INT NULL AFTER `group`;");

