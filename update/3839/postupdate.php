<?php

// get db connection
$db = Pimcore_Resource::get();

try {

    $db->query("ALTER TABLE `quantityvalue_units`
	CHANGE COLUMN `referemce` `reference` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_bin' AFTER `conversionOffset`;
    ");

} catch (\Exception $e) {

}

