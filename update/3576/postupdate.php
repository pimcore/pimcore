<?php

// get db connection
$db = Pimcore_Resource::get();

$db->query("ALTER TABLE `classificationstore_keys`
	ADD COLUMN `title` VARCHAR(255) NOT NULL DEFAULT '' AFTER `name`,
	ADD INDEX `name` (`name`),
	ADD INDEX `enabled` (`enabled`),
	ADD INDEX `type` (`type`);
");

$db->query("ALTER TABLE `classificationstore_groups`
	ADD INDEX `name` (`name`);
");

$db->query("ALTER TABLE `classificationstore_collections`
	ADD INDEX `name` (`name`);
");