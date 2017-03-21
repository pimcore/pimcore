<?php

// get db connection
$db = Pimcore_Resource::get();

$db->query("ALTER TABLE `classificationstore_groups`
	DROP COLUMN `sorter`;
");

$db->query("ALTER TABLE `classificationstore_keys`
	DROP COLUMN `sorter`;
");

$db->query("ALTER TABLE `classificationstore_collectionrelations`
	MODIFY COLUMN `sorter` INT NULL AFTER `groupId`;
");

$db->query("ALTER TABLE `classificationstore_relations`
	MODIFY COLUMN `sorter` INT NOT NULL AFTER `keyId`;
");
