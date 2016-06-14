<?php

$db = \Pimcore\Db::get();
$db->query("ALTER TABLE `users` CHANGE COLUMN `permissions` `permissions` text;");
