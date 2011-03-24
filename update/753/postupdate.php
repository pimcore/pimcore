<?php

// get db connection
$db = Pimcore_Resource_Mysql::get("database");
$db->getConnection()->exec("ALTER TABLE `classes` ADD UNIQUE  (`name`);");