<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();


try {
    $db->exec("ALTER TABLE `objects` ADD INDEX `type` (`o_type`);");
} catch (Exception $e) {}
    
