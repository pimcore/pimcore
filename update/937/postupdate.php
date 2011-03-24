<?php

// get db connection
$db = Pimcore_Resource_Mysql::get("database");

$db->exec("ALTER TABLE `search_backend_data` ADD COLUMN `fieldcollectiondata` LONGTEXT NULL;");
$db->exec("ALTER TABLE `search_backend_data` ADD COLUMN `localizeddata` LONGTEXT NULL;");