<?php

$sql = "ALTER TABLE `documents_page` ADD COLUMN `module` varchar(255) NULL DEFAULT NULL AFTER `id`;";
$sql = "ALTER TABLE `documents_snippet` ADD COLUMN `module` varchar(255) NULL DEFAULT NULL AFTER `id`;";
$sql = "ALTER TABLE `documents_email` ADD COLUMN `module` varchar(255) NULL DEFAULT NULL AFTER `id`;";
$sql = "ALTER TABLE `documents_doctypes` ADD COLUMN `module` varchar(255) NULL DEFAULT NULL AFTER `name`;";

function sendQuery ($sql) {
    try {
        $db = Pimcore_Resource::get();
        $db->query($sql);
    } catch (Exception $e) {
        echo $e->getMessage();
        echo "Please execute the following query manually: <br />";
        echo $sql;
    }
}
