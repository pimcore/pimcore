<?php

// DOCUMENT TYPES
$file = Pimcore\Config::locateConfigFile("document-types");
$db = \Pimcore\Db::get();
$staticRoutes = $db->fetchAll("SELECT * FROM documents_doctypes");

$json = \Pimcore\Db\JsonFileTable::get($file);
$json->truncate();

foreach($staticRoutes as $route) {
    $data = $route;
    unset($data["id"]);
    $json->insertOrUpdate($data, $route["id"]);
}

$db->query("RENAME TABLE `documents_doctypes` TO `PLEASE_DELETE__documents_doctypes`;");


// PREDEFINED PROPERTIES
$file = Pimcore\Config::locateConfigFile("predefined-properties");
$db = \Pimcore\Db::get();
$staticRoutes = $db->fetchAll("SELECT * FROM properties_predefined");

$json = \Pimcore\Db\JsonFileTable::get($file);
$json->truncate();

foreach($staticRoutes as $route) {
    $data = $route;
    unset($data["id"]);
    $json->insertOrUpdate($data, $route["id"]);
}

$db->query("RENAME TABLE `properties_predefined` TO `PLEASE_DELETE__properties_predefined`;");

