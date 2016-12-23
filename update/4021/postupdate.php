<?php

$db = \Pimcore\Db::get();

$db->query("ALTER TABLE `assets_metadata` ADD INDEX `name` (`name`);");

