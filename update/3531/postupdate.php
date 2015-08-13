<?php

// get db connection
$db = Pimcore_Resource::get();

$db->query("CREATE TABLE `classificationstore_collections` (
	`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	`description` VARCHAR(255) NULL DEFAULT NULL,
	`creationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	`modificationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;");

$db->query("CREATE TABLE `classificationstore_collectionrelations` (
	`colId` BIGINT(20) NOT NULL,
	`groupId` BIGINT(20) NOT NULL,
	PRIMARY KEY (`colId`, `groupId`),
	CONSTRAINT `FK_classificationstore_collectionrelations_groups` FOREIGN KEY (`groupId`) REFERENCES `classificationstore_groups` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8;");
