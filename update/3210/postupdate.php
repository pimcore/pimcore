<?php

// get db connection
$db = Pimcore_Resource::get();

try{
    // objectsmetadata inside localized fields/bricks/fieldcollection
    $tables = $db->fetchAll("SHOW TABLES LIKE 'object_metadata_%'");

    foreach ($tables as $table) {
        $t = current($table);

        $sql =  "ALTER TABLE `" . $t. "`
        ADD COLUMN `ownertype` ENUM('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object' AFTER `data`,
        ADD COLUMN `ownername` VARCHAR(70) NOT NULL DEFAULT '' AFTER `ownertype`,
        ADD COLUMN `position` VARCHAR(70) NOT NULL DEFAULT '0' AFTER `ownername`,
        DROP PRIMARY KEY,
        ADD PRIMARY KEY (`o_id`, `dest_id`, `fieldname`, `column`, `ownertype`, `ownername`, `position`),
        ADD INDEX `ownertype` (`ownertype`),
        ADD INDEX `ownername` (`ownername`),
        ADD INDEX `position` (`position`);";

        $db->query($sql);
    }
} catch (\Exception $e) {
    echo $e->getMessage();
}
