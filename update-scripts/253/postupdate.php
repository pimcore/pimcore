<?php

$db = \Pimcore\Db::get();

$db->query('ALTER TABLE `users`
	ADD COLUMN `keyBindings` TEXT NULL AFTER `lastLogin`;
');
