<?php

// get db connection
$db = Pimcore_Resource::get();


$tables = $db->fetchAll("SHOW TABLES LIKE 'object_classificationstore_data_%'");

foreach ($tables as $table) {
    $t = current($table);

    $db->query("ALTER TABLE `" . $t . "`
	    CHANGE COLUMN `value` `value` LONGTEXT NULL AFTER `keyId`;
    ");

}


