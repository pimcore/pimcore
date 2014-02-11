<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();


// by CF
// add objectbrick to ownertype enum of relations tables
$tables = $db->fetchAll("SHOW TABLES LIKE 'object_relations_%'");
foreach ($tables as $table) {
    $t = current($table);

    $db->exec("ALTER TABLE `" . $t . "` CHANGE ownertype ownertype ENUM('object', 'fieldcollection', 'localizedfield','objectbrick') NOT NULL DEFAULT 'object';");
}



// by BR

// change unique key to primary key
try {
    $db->exec("ALTER TABLE `users_permissions` ADD PRIMARY KEY (`userId`,`name`(255)), DROP INDEX `userid_permission`;");
} catch (Exception $e) {}
    
// add primary key to all fieldcollection tables
$tables = $db->fetchAll("SHOW TABLES LIKE 'object_collection_%'");
foreach ($tables as $table) {
    $t = current($table);

    try {
        $db->exec("ALTER TABLE `" . $t . "` ADD PRIMARY KEY (`o_id`,`index`,`fieldname`);");
    } catch (Exception $e) {}
}

// add primary key to all localized field tables
$tables = $db->fetchAll("SHOW TABLES LIKE 'object_localized_data_%'");
foreach ($tables as $table) {
    $t = current($table);

    try {
        $db->exec("ALTER TABLE `" . $t . "` ADD PRIMARY KEY (`ooo_id`,`language`);");
    } catch (Exception $e) {}
}

