<?php

// get db connection
$db = Pimcore_Resource::get();


$tables = $db->fetchAll("SHOW TABLES LIKE 'object_metadata_%'");

foreach ($tables as $table) {
    $t = current($table);

    $db->query("ALTER TABLE `" . $t . "`
        ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT '' AFTER `dest_id`,
        DROP PRIMARY KEY,
        ADD PRIMARY KEY (`o_id`, `dest_id`, `fieldname`, `column`, `ownertype`, `ownername`, `position`, `type`),
        ADD INDEX `type` (`type`);
    ");

}




