<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->query("ALTER TABLE `users` ADD COLUMN `memorizeTabs` tinyint(1)  NULL DEFAULT NULL;");
$db->query("UPDATE `users` SET `memorizeTabs`=1;");