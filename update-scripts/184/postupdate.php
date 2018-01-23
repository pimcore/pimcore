<?php

$db = \Pimcore\Db::get();
$db->query('ALTER TABLE `versions` DROP COLUMN `binaryDataHash`;');
