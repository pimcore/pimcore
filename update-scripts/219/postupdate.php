<?php

$db = \Pimcore\Db::get();

$db->query("ALTER TABLE `objects` ADD `o_childrenSortBy` ENUM('key','index') NULL DEFAULT NULL AFTER `o_className`;");
$db->query('ALTER TABLE `objects` ADD INDEX `index` (`o_index`);');
