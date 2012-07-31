<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->query("ALTER TABLE `redirects` ADD COLUMN `sourceEntireUrl` tinyint(1) NULL DEFAULT NULL AFTER `source`;");
$db->query("ALTER TABLE `redirects` ADD COLUMN `sourceSite` int(11) NULL DEFAULT NULL AFTER `sourceEntireUrl`;");
$db->query("ALTER TABLE `redirects` ADD COLUMN `targetSite` int(11) NULL DEFAULT NULL AFTER `target`;");
