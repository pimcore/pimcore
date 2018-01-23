<?php

$db = \Pimcore\Db::get();
$db->query('ALTER TABLE `versions` ADD COLUMN `binaryDataHash` VARCHAR(40) NULL DEFAULT NULL;');
$db->query('ALTER TABLE `versions` ADD INDEX `binaryDataHash` (`binaryDataHash`);');
