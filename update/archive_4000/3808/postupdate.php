<?php

$db = \Pimcore\Db::get();

// add missing indexes
$db->query("ALTER TABLE `redirects` ADD INDEX `active` (`active`);");
$db->query("ALTER TABLE `classificationstore_relations` ADD INDEX `mandatory` (`mandatory`);");
