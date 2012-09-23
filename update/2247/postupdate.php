<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->query("ALTER TABLE `staticroutes` ADD COLUMN `siteId` int(11) NULL DEFAULT NULL AFTER `defaults`;");

