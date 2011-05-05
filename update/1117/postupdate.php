<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();


// add objectbrick to ownertype enum of relations tables
$tables = $db->fetchAll("SHOW TABLES LIKE 'object_relations_%'");

foreach ($tables as $table) {
    $t = current($table);

    $db->exec("ALTER TABLE `" . $t . "` CHANGE ownertype ownertype ENUM('object', 'fieldcollection', 'localizedfield','objectbrick') NOT NULL DEFAULT 'object';");
}


