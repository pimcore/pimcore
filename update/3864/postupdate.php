<?php

$db = \Pimcore\Db::get();
$db->query("ALTER TABLE `classes` ADD COLUMN `group` varchar(255) NULL DEFAULT NULL AFTER `showVariants`;");
