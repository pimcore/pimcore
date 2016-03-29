<?php

$db = Pimcore\Db::get();



$db->query("DROP TABLE IF EXISTS `classificationstore_stores`;");


$db->query("CREATE TABLE `classificationstore_stores` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NULL DEFAULT NULL,
	`description` LONGTEXT NULL,
	PRIMARY KEY (`id`),
	INDEX `name` (`name`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
;
");

$db->query("INSERT INTO `pimcore`.`classificationstore_stores` (`id`, `name`, `description`) VALUES (1, 'Default', 'Default Store');");


$db->query("ALTER TABLE `classificationstore_keys`
	ADD COLUMN `storeId` INT NULL DEFAULT NULL AFTER `id`,
	ADD INDEX `storeId` (`storeId`);
");

$db->query("ALTER TABLE `classificationstore_groups`
	ADD COLUMN `storeId` INT NULL DEFAULT NULL AFTER `id`,
	ADD INDEX `storeId` (`storeId`);
");

$db->query("ALTER TABLE `classificationstore_collections`
	ADD COLUMN `storeId` INT NULL DEFAULT NULL AFTER `id`,
	ADD INDEX `storeId` (`storeId`);
");

$db->query("ALTER TABLE `classificationstore_relations`
	ADD COLUMN `storeId` INT NOT NULL FIRST,
	DROP PRIMARY KEY,
	ADD PRIMARY KEY (`groupId`, `keyId`, `storeId`),
	ADD INDEX `storeId` (`storeId`);
");

$db->query("ALTER TABLE `classificationstore_collectionrelations`
	ADD COLUMN `storeId` INT NOT NULL FIRST,
	DROP PRIMARY KEY,
	ADD PRIMARY KEY (`colId`, `groupId`, `storeId`),
	ADD INDEX `storeId` (`storeId`);
");

$db->query("UPDATE classificationstore_keys set storeId = 1");
$db->query("UPDATE classificationstore_groups set storeId = 1");
$db->query("UPDATE classificationstore_collections set storeId = 1");
$db->query("UPDATE classificationstore_relations set storeId = 1");
$db->query("UPDATE classificationstore_collectionrelations set storeId = 1");

