<?php
/**
 * Created by PhpStorm.
 * User: tmittendorfer
 * Date: 08.06.2018
 * Time: 14:13
 */

$db = \Pimcore\Db::get();

$db->query('ALTER TABLE `users` ADD COLUMN `twoFactorAuthentication` VARCHAR(255) AFTER `apiKey`');
