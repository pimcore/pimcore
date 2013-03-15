<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();
$db->query("ALTER TABLE `classes` ADD COLUMN `treeLabelField` varchar(255) DEFAULT NULL AFTER `previewUrl`;");


