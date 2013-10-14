<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `targeting_rules` ADD COLUMN `active` tinyint(1) NULL DEFAULT NULL AFTER `scope`;");
$db->query("ALTER TABLE `targeting_personas` ADD COLUMN `active` tinyint(1) NULL DEFAULT NULL;");

