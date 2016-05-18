<?php

$db = \Pimcore\Db::get();
$db->query("ALTER TABLE `redirects` ADD COLUMN `active` tinyint(1) NULL DEFAULT NULL AFTER `priority`;");
