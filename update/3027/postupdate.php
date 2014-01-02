<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `users` ADD COLUMN `docTypes` varchar(255) NULL DEFAULT NULL;");
$db->query("ALTER TABLE `users` ADD COLUMN `classes` varchar(255) NULL DEFAULT NULL;");
