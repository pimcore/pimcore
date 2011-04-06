<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();
$db->exec("ALTER TABLE `thumbnails` ADD COLUMN `description` text NULL AFTER `name`;");




