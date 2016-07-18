<?php

// STATICROUTES
$file = Pimcore\Config::locateConfigFile("staticroutes");
$db = \Pimcore\Db::get();
$staticRoutes = $db->fetchAll("SELECT * FROM staticroutes");

$json = \Pimcore\Db\JsonFileTable::get($file);
$json->truncate();

foreach($staticRoutes as $route) {
    $data = $route;
    unset($data["id"]);
    $json->insertOrUpdate($data, $route["id"]);
}

$db->query("RENAME TABLE `staticroutes` TO `PLEASE_DELETE__staticroutes`;");


