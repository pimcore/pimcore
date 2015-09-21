<?php

// get db connection
$db = Pimcore_Resource::get();

$db->query("CREATE TABLE `classificationstore_groups` (
	`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	`parentId` BIGINT(20) NOT NULL DEFAULT '0',
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	`description` VARCHAR(255) NULL DEFAULT NULL,
	`creationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	`modificationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	PRIMARY KEY (`id`)
)
DEFAULT CHARSET=utf8;");

	$db->query("CREATE TABLE `classificationstore_keys` (
	`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	`description` TEXT NULL,
	`type` ENUM('input','textarea','wysiwyg','checkbox','numeric','slider','select','multiselect','date','datetime','language','languagemultiselect','country','countrymultiselect','table') NULL DEFAULT NULL,
	`creationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	`modificationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	`definition` LONGTEXT NULL,
	`enabled` TINYINT(1) NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
)
DEFAULT CHARSET=utf8;");

$db->query("CREATE TABLE `classificationstore_relations` (
	`groupId` BIGINT(20) NOT NULL,
	`keyId` BIGINT(20) NOT NULL,
	PRIMARY KEY (`groupId`, `keyId`),
	INDEX `FK_classificationstore_relations_classificationstore_keys` (`keyId`),
	CONSTRAINT `FK_classificationstore_relations_classificationstore_groups` FOREIGN KEY (`groupId`) REFERENCES `classificationstore_groups` (`id`) ON DELETE CASCADE,
	CONSTRAINT `FK_classificationstore_relations_classificationstore_keys` FOREIGN KEY (`keyId`) REFERENCES `classificationstore_keys` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)

DEFAULT CHARSET=utf8;");