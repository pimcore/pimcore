<?php

// get db connection
$db = \Pimcore\Db::get();

$db->query("ALTER TABLE `application_logs`
	ADD COLUMN `pid` INT NULL DEFAULT NULL AFTER `id`;
");
