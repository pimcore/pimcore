<?php

// get db connection
$db = Pimcore_Resource::get();

$tables = $db->fetchAll("SHOW TABLES LIKE 'object_classificationstore_data_%'");

foreach ($tables as $table) {
    $t = current($table);

    $db->query("ALTER TABLE `" . $t . "`
            ADD INDEX `o_id` (`o_id`),
            ADD INDEX `groupId` (`groupId`),
            ADD INDEX `keyId` (`keyId`),
            ADD INDEX `fieldname` (`fieldname`),
            ADD INDEX `language` (`language`);
    ");

}


