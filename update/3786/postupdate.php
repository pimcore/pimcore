<?php

$db = \Pimcore\Db::get();
$db->query("ALTER TABLE `redirects` ADD COLUMN `active` tinyint(1) NULL DEFAULT NULL AFTER `priority`;");
$db->update("redirects", ["active" => 1]); // set all existing redirects to active
