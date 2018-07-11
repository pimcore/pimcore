<?php

$db = \Pimcore\Db::get();

$db->query('ALTER TABLE `users` ADD COLUMN `twoFactorAuthentication` VARCHAR(255) AFTER `apiKey`');
