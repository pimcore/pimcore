<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();


try {
    $db->exec("ALTER TABLE `staticroutes` ADD COLUMN module varchar(255) NULL");
} catch (Exception $e) {}
    
