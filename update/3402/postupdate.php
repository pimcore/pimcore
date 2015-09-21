<?php

$db = \Pimcore\Resource::get();
$db->query("ALTER TABLE `classes` ADD COLUMN `useTraits` varchar(255) NULL DEFAULT NULL AFTER `parentClass`;");

