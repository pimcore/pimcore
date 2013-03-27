<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `targeting` DROP INDEX `name_documentId`;");
$db->query("ALTER TABLE `targeting` DROP INDEX `documentId`;");
$db->query("ALTER TABLE `targeting` DROP COLUMN `documentId`;");
