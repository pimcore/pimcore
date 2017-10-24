<?php

$db = \Pimcore\Db::get();

$db->query('
CREATE TABLE `gridconfigs` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`ownerId` INT(11) NULL,
	`classId` INT(11) NULL,
	`name` VARCHAR(50) NULL,
	`searchType` VARCHAR(50) NULL,
	`config` LONGTEXT NULL,
    `description` LONGTEXT NULL,	
	`creationDate` INT(11) NULL,
	`modificationDate` INT(11) NULL,
	PRIMARY KEY (`id`),
	INDEX `ownerId` (`ownerId`),
	INDEX `classId` (`classId`),
	INDEX `searchType` (`searchType`)
)
DEFAULT CHARSET=utf8mb4;
;
');

$db->query('
CREATE TABLE `gridconfig_favourites` (
	`ownerId` INT(11) NOT NULL,
	`classId` INT(11) NOT NULL,
	`objectId` INT(11) NOT NULL DEFAULT \'0\',
	`gridConfigId` INT(11) NULL,
	`searchType` VARCHAR(50) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`ownerId`, `classId`, `searchType`, `objectId`),
	INDEX `ownerId` (`ownerId`),
	INDEX `classId` (`classId`),
	INDEX `searchType` (`searchType`)
)
DEFAULT CHARSET=utf8mb4;
;
');

$db->query('
CREATE TABLE `gridconfig_shares` (
	`gridConfigId` INT(11) NOT NULL,
	`sharedWithUserId` INT(11) NOT NULL,
	PRIMARY KEY (`gridConfigId`, `sharedWithUserId`),
	INDEX `gridConfigId` (`gridConfigId`),
	INDEX `sharedWithUserId` (`sharedWithUserId`)
)
DEFAULT CHARSET=utf8mb4;
;
');
