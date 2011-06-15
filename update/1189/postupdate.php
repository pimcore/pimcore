<?php

// get db connection
$db = Pimcore_Resource::get();


// rename column "type" to "ctype" and create the new columns "type" and "position"
$tables = $db->fetchAll("SHOW TABLES LIKE 'object_relations_%'");

foreach ($tables as $table) {
    $t = current($table);
    $db->exec("ALTER TABLE `" . $t . "` CHANGE COLUMN `type` `type` varchar(50) NULL DEFAULT NULL;");
}
