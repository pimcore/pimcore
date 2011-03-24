<?php


// get db connection
$db = Pimcore_Resource_Mysql::get("database");

try {
    $db->getConnection()->exec("ALTER TABLE classes CHANGE COLUMN `layoutDefinitions` `layoutDefinitions` longtext NULL;");
}
catch (Exception $e) {}

?>