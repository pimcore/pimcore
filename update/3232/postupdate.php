<?php

// get db connection
$db = Pimcore_Resource::get();

try{
    $db->query("ALTER TABLE `keyvalue_keys` ADD COLUMN `mandatory` TINYINT(1) NULL DEFAULT NULL AFTER `translator`;");
} catch (\Exception $e) {
    echo $e->getMessage();
}
