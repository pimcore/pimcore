<?php

$db = Pimcore_Resource_Mysql::get("database");
$db->getConnection()->exec("ALTER TABLE `properties_predefined` CHANGE COLUMN `type` `type` enum('text','document','asset','bool','select','object') NULL DEFAULT NULL;");

?>