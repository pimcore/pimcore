<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("CREATE TABLE `custom_layouts` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`classId` INT(11) UNSIGNED NOT NULL,
	`name` VARCHAR(255) NULL DEFAULT NULL,
	`description` TEXT NULL,
	`creationDate` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`modificationDate` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`userOwner` INT(11) UNSIGNED NULL DEFAULT NULL,
	`userModification` INT(11) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `name` (`name`, `classId`)
) DEFAULT CHARSET=utf8;");


$db->query("ALTER TABLE `users_workspaces_object`
	ADD COLUMN `layouts` TEXT NULL AFTER `lView`;
");
