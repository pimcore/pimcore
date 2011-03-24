<?php

    // get db connection
    $db = Pimcore_Resource_Mysql::get("database");
    $db->getConnection()->exec("INSERT INTO `users_permission_definitions` SET `key`='forms',`translation`='permission_forms';");
    
?>