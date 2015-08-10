<?php

// get db connection
$db = Pimcore_Resource::get();

$db->query("ALTER TABLE `classificationstore_keys` ADD COLUMN `sorter` INT(10) NULL DEFAULT '0' AFTER `enabled`;");
$db->query("ALTER TABLE `classificationstore_groups` ADD COLUMN `sorter` INT(10) NULL DEFAULT '0' AFTER `modificationDate`;");
