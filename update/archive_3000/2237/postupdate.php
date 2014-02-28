<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->query("ALTER TABLE `documents_link` CHANGE COLUMN `direct` `direct` varchar(1000) NULL DEFAULT NULL;");

