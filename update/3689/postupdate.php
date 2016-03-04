<?php

$db = Pimcore\Db::get();

$db->query("ALTER TABLE `classificationstore_relations` ADD INDEX `groupId` (`groupId`);");

$db->query("ALTER TABLE `classificationstore_collectionrelations` ADD INDEX `colId` (`colId`);
");



