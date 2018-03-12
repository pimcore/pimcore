<?php

$db = \Pimcore\Db::get();
$db->query('ALTER TABLE assets ROW_FORMAT=DYNAMIC;');
$db->query('ALTER TABLE documents ROW_FORMAT=DYNAMIC;');
$db->query('ALTER TABLE objects ROW_FORMAT=DYNAMIC;');
$db->query('ALTER TABLE properties ROW_FORMAT=DYNAMIC;');
$db->query('ALTER TABLE search_backend_data ROW_FORMAT=DYNAMIC;');
$db->query('ALTER TABLE users_workspaces_asset ROW_FORMAT=DYNAMIC;');
$db->query('ALTER TABLE users_workspaces_document ROW_FORMAT=DYNAMIC;');
$db->query('ALTER TABLE users_workspaces_object ROW_FORMAT=DYNAMIC;');

$db->query("ALTER TABLE `assets`
	CHANGE COLUMN `filename` `filename` VARCHAR(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'  NULL DEFAULT ''AFTER `type`,
	CHANGE COLUMN `path` `path` VARCHAR(765) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL DEFAULT NULL AFTER `filename`;
");

$db->query("ALTER TABLE `documents`
	CHANGE COLUMN `key` `key` VARCHAR(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'  NULL DEFAULT ''AFTER `type`,
	CHANGE COLUMN `path` `path` VARCHAR(765) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL DEFAULT NULL AFTER `key`;
");

$db->query("ALTER TABLE `objects`
	CHANGE COLUMN `o_key` `o_key` VARCHAR(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'  NULL DEFAULT ''AFTER `o_type`,
	CHANGE COLUMN `o_path` `o_path` VARCHAR(765) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL DEFAULT NULL AFTER `o_key`;
");

$db->query("ALTER TABLE `properties`
	CHANGE COLUMN `cpath` `cpath` VARCHAR(765) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'  NULL DEFAULT ''AFTER `ctype`;
");

$db->query("ALTER TABLE `search_backend_data`
	CHANGE COLUMN `fullpath` `fullpath` VARCHAR(765) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'  NULL DEFAULT ''AFTER `id`;
");

$db->query("ALTER TABLE `users_workspaces_asset`
	CHANGE COLUMN `cpath` `cpath` VARCHAR(765) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'  NULL DEFAULT ''AFTER `cid`;
");

$db->query("ALTER TABLE `users_workspaces_document`
	CHANGE COLUMN `cpath` `cpath` VARCHAR(765) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'  NULL DEFAULT ''AFTER `cid`;
");

$db->query("ALTER TABLE `users_workspaces_object`
	CHANGE COLUMN `cpath` `cpath` VARCHAR(765) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'  NULL DEFAULT ''AFTER `cid`;
");
