<?php

// get db connection
$db = Pimcore_Resource::get();

$db->query("ALTER TABLE `users`
	ADD COLUMN `contentLanguages` LONGTEXT NULL DEFAULT NULL AFTER `language`;
");
