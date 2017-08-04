<?php

$db = \Pimcore\Db::get();
$db->query("ALTER TABLE `tags` CHANGE COLUMN `idPath` `idPath` VARCHAR(190) NULL DEFAULT NULL;");
$db->query("ALTER TABLE `tracking_events` CHANGE COLUMN `category` `category` VARCHAR(190) NULL DEFAULT NULL;");
$db->query("ALTER TABLE `tracking_events` CHANGE COLUMN `action` `action` VARCHAR(190) NULL DEFAULT NULL;");
$db->query("ALTER TABLE `tracking_events` CHANGE COLUMN `label` `label` VARCHAR(190) NULL DEFAULT NULL;");
$db->query("ALTER TABLE `tracking_events` CHANGE COLUMN `data` `data` VARCHAR(190) NULL DEFAULT NULL;");
$db->query("ALTER TABLE `users` CHANGE COLUMN `password` `password` VARCHAR(190) NULL DEFAULT NULL;");
$db->query("ALTER TABLE `website_settings` CHANGE COLUMN `name` `name` VARCHAR(190) NULL DEFAULT '';");
$db->query("ALTER TABLE `classificationstore_stores` CHANGE COLUMN `name` `name` VARCHAR(190) NULL DEFAULT NULL;");
$db->query("ALTER TABLE `classificationstore_groups` CHANGE COLUMN `name` `name` VARCHAR(190) NULL DEFAULT '';");
$db->query("ALTER TABLE `classificationstore_keys` CHANGE COLUMN `name` `name` VARCHAR(190) NULL DEFAULT '';");
$db->query("ALTER TABLE `classificationstore_keys` CHANGE COLUMN `type` `type` VARCHAR(190) NULL DEFAULT NULL;");
