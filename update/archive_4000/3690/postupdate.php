<?php

$db = Pimcore\Db::get();

$db->query("ALTER TABLE `classificationstore_relations` ALTER `sorter` DROP DEFAULT;");

$db->query("ALTER TABLE `classificationstore_relations` CHANGE COLUMN `sorter` `sorter` INT(11) NULL AFTER `keyId`;");



