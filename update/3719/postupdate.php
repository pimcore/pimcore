<?php

$db = Pimcore\Db::get();



$db->query("ALTER TABLE `users`
	ADD COLUMN `perspective` VARCHAR(255) NULL DEFAULT NULL AFTER `apiKey`,
	ADD COLUMN `availablePerspectives` LONGTEXT NULL DEFAULT NULL AFTER `perspective`;
");
