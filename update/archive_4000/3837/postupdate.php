<?php

// get db connection
$db = Pimcore_Resource::get();

try {

    $db->query("ALTER TABLE `quantityvalue_units`
        ADD COLUMN `referemce` VARCHAR(50) NULL DEFAULT NULL AFTER `conversionOffset`;
    ");

} catch (\Exception $e) {

}

