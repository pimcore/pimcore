<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();


// rename column "type" to "ctype" and create the new columns "type" and "position"
$tables = $db->fetchAll("SHOW TABLES LIKE 'object_relations_%'");

foreach ($tables as $table) {
    $t = current($table);

    $db->exec("ALTER TABLE `" . $t . "` CHANGE COLUMN `type` `type` enum('asset','document','object') NULL DEFAULT NULL;");
    $db->exec("ALTER TABLE `" . $t . "` ADD COLUMN `ownertype` enum('object','fieldcollection','localizedfield') NOT NULL DEFAULT 'object';");
    $db->exec("ALTER TABLE `" . $t . "` ADD COLUMN `ownername` varchar(70) NULL DEFAULT NULL;");
    $db->exec("ALTER TABLE `" . $t . "` ADD COLUMN `position` int(11) NULL DEFAULT NULL;");

    // primary key
    $db->exec("ALTER TABLE `" . $t . "` ADD PRIMARY KEY (`src_id`,`dest_id`,`ownertype`,`ownername`(70),`fieldname`(255),`type`,`position`), DROP PRIMARY KEY;");

    // indices
    $db->exec("ALTER TABLE `" . $t . "` ADD INDEX `src_id` (`src_id`);");
    $db->exec("ALTER TABLE `" . $t . "` ADD INDEX `dest_id` (`dest_id`);");
    $db->exec("ALTER TABLE `" . $t . "` ADD INDEX `ownertype` (`ownertype`);");
    $db->exec("ALTER TABLE `" . $t . "` ADD INDEX `ownername` (`ownername`(70));");
    $db->exec("ALTER TABLE `" . $t . "` ADD INDEX `fieldname` (`fieldname`);");
    $db->exec("ALTER TABLE `" . $t . "` ADD INDEX `type` (`type`);");
    $db->exec("ALTER TABLE `" . $t . "` ADD INDEX `position` (`position`);");
}


