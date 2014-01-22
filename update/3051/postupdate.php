<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `users_workspaces_object`
	ADD COLUMN `lEdit` TEXT NULL DEFAULT NULL AFTER `properties`,
	ADD COLUMN `lView` TEXT NULL DEFAULT NULL AFTER `lEdit`;
");
