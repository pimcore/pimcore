<?php

// get db connection
$db = Pimcore_Resource::get();

$tables = $db->fetchAll("SHOW TABLES LIKE 'object_classificationstore_data_%'");

foreach ($tables as $table) {
    $t = current($table);

    // migrate the quantity value records
    $db->query("UPDATE `" . $t . "`
        set value2 = TRIM(BOTH '\"' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(value,';',4),':',-1)), value = TRIM(BOTH '\"' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(value,';',2),':',-1)) where type = 'quantityValue';
    ");

}


