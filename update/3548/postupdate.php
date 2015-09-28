<?php

// get db connection
$db = Pimcore_Resource::get();

$db->query("ALTER TABLE `classificationstore_collectionrelations`
	ADD COLUMN `sorter` INT(10) NULL DEFAULT '0' AFTER `groupId`;
");

$db->query("ALTER TABLE `classificationstore_relations`
	ADD COLUMN `sorter` INT(10) NULL DEFAULT '0' AFTER `keyId`;
");

$db->query("ALTER TABLE `classificationstore_groups`
	DROP COLUMN `sorter`;
");

$db->query("ALTER TABLE `classificationstore_keys`
	DROP COLUMN `sorter`;
");

$tables = $db->fetchAll("SHOW TABLES LIKE 'object_classificationstore_data_%'");

foreach ($tables as $table) {
    $t = current($table);

    $db->query("ALTER TABLE `" . $t . "`
	    ADD COLUMN `collectionId` BIGINT(20) NULL AFTER `o_id`;
    ");

}


