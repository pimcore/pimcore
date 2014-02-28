<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `targeting_rules` ADD COLUMN `scope` varchar(50) NULL DEFAULT NULL AFTER `description`;");
