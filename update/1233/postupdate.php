<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();


try {
    $db->exec("ALTER TABLE `classes` ADD COLUMN `previewUrl` varchar(255) DEFAULT NULL AFTER `icon`;");
} catch (Exception $e) {}
    
