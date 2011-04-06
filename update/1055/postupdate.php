<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();
$db->exec("ALTER TABLE `sites` CHANGE COLUMN `domains` `domains` text NULL;");


