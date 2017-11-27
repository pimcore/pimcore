<?php

$db = \Pimcore\Db::get();

$db->query('
CREATE TABLE `importconfigs` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`ownerId` INT(11) NULL,
	`classId` INT(11) NULL,
	`name` VARCHAR(50) NULL,
	`config` LONGTEXT NULL,
    `description` LONGTEXT NULL,	
	`creationDate` INT(11) NULL,
	`modificationDate` INT(11) NULL,
	`shareGlobally` TINYINT(1) NULL,
	PRIMARY KEY (`id`),
	INDEX `ownerId` (`ownerId`),
	INDEX `classId` (`classId`),
	INDEX `shareGlobally` (`shareGlobally`)
)
DEFAULT CHARSET=utf8mb4;
;
');

$db->query('
CREATE TABLE `importconfig_shares` (
	`importConfigId` INT(11) NOT NULL,
	`sharedWithUserId` INT(11) NOT NULL,
	PRIMARY KEY (`importConfigId`, `sharedWithUserId`),
	INDEX `importConfigId` (`importConfigId`),
	INDEX `sharedWithUserId` (`sharedWithUserId`)
)
DEFAULT CHARSET=utf8mb4;
;
');

$db->query('
ALTER TABLE `gridconfigs`
	ADD COLUMN `shareGlobally` TINYINT(1) NULL DEFAULT NULL AFTER `creationDate`,
	ADD INDEX `shareGlobally` (`shareGlobally`);
;
');
