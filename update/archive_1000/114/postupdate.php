<?php


// get db connection
$db = Pimcore_Resource_Mysql::get("database");

try {
    $db->getConnection()->exec("ALTER TABLE `users_permissions` DROP PRIMARY KEY;");
    $db->getConnection()->exec("ALTER TABLE `users_permissions` DROP COLUMN `Id`;");
}
catch (Exception $e) {}


?>