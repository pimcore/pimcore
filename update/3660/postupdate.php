<?php

// get db connection
$db = Pimcore_Resource::get();

$tables = $db->fetchAll("SHOW TABLES LIKE 'object_classificationstore_groups_%'");

foreach ($tables as $table) {
    $t = current($table);

    // migrate the quantity value records
    $db->query("ALTER TABLE `" . $t . "`
        ADD INDEX `o_id` (`o_id`);
    ");

}


