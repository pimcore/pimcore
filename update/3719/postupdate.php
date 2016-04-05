<?php

$db = Pimcore\Db::get();



$db->query("ALTER TABLE `users`
	ADD COLUMN `activePerspective` VARCHAR(255) NULL DEFAULT NULL AFTER `apiKey`,
	ADD COLUMN `perspectives` LONGTEXT NULL DEFAULT NULL AFTER `activePerspective`;
");
