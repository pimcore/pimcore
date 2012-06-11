<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();


// rename column "type" to "ctype" and create the new columns "type" and "position"
$tables = $db->fetchAll("SHOW TABLES LIKE 'object_relations_%'");

foreach ($tables as $table) {
    $t = current($table);
    $db->query("ALTER TABLE `" . $t . "` CHANGE COLUMN `position` `position` varchar(70) NULL DEFAULT NULL;");
}
