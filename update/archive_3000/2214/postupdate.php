<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$tables = $db->fetchAll("SHOW TABLES LIKE 'object_localized_data_%'");

foreach ($tables as $table) {
    $t = current($table);
    $db->query("ALTER TABLE `" . $t . "` CHANGE COLUMN `language` `language` varchar(10) NOT NULL DEFAULT '';");
}

