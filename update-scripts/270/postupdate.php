<?php
$db = \Pimcore\Db::get();
$db->query('ALTER TABLE `classes`
	ALTER `id` DROP DEFAULT;
');

$db->query('ALTER TABLE `classes`
	CHANGE COLUMN `id` `id` VARCHAR(50) NOT NULL FIRST;');

$db->query('ALTER TABLE `objects`
	CHANGE COLUMN `o_classId` `o_classId` VARCHAR(50) NULL DEFAULT NULL AFTER `o_userModification`;');

$db->query('ALTER TABLE `gridconfigs`
	CHANGE COLUMN `classId` `classId` VARCHAR(50) NULL DEFAULT NULL AFTER `ownerId`;
');

$db->query('ALTER TABLE `gridconfig_favourites`
	ALTER `classId` DROP DEFAULT;');

$db->query('ALTER TABLE `gridconfig_favourites`
	CHANGE COLUMN `classId` `classId` VARCHAR(50) NOT NULL AFTER `ownerId`;');

$db->query('ALTER TABLE `importconfigs`
	CHANGE COLUMN `classId` `classId` VARCHAR(50) NULL DEFAULT NULL AFTER `ownerId`;
');

$db->query('ALTER TABLE `custom_layouts`
	ALTER `classId` DROP DEFAULT;
');

$db->query('ALTER TABLE `custom_layouts`
	CHANGE COLUMN `classId` `classId` VARCHAR(50) NOT NULL AFTER `id`;');
