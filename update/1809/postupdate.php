<?php

function sendQuery ($sql) {
    try {
        $db = Pimcore_Resource::get();
        $db->query($sql);
    } catch (Exception $e) {
        echo $e->getMessage();
        echo "Please execute the following query manually: <br />";
        echo "<pre>" . $sql . "</pre><hr />";
    }
}

sendQuery("CREATE TABLE `tree_locks` (
  `id` int(11) NOT NULL DEFAULT '0',
  `type` enum('asset','document','object') NOT NULL DEFAULT 'asset',
  `locked` enum('self','propagate') default NULL,
  PRIMARY KEY (`id`,`type`),
  KEY `id` (`id`),
  KEY `type` (`type`),
  KEY `locked` (`locked`)
) DEFAULT CHARSET=utf8;");


$db = Pimcore_Resource::get();

// assets
$assetLocks = $db->fetchAll("SELECT id,path,filename,locked FROM assets WHERE locked IS NOT NULL AND locked != ''");
foreach ($assetLocks as $lock) {
    $db->insert("tree_locks", array(
        "id" => $lock["id"],
        "type" => "asset",
        "locked" => $lock["locked"]
    ));
}
sendQuery("ALTER TABLE `assets` DROP COLUMN `locked`;");

// documents
$documentLocks = $db->fetchAll("SELECT id,path,`key`,locked FROM documents WHERE locked IS NOT NULL AND locked != ''");
foreach ($documentLocks as $lock) {
    $db->insert("tree_locks", array(
        "id" => $lock["id"],
        "type" => "document",
        "locked" => $lock["locked"]
    ));
}
sendQuery("ALTER TABLE `documents` DROP COLUMN `locked`;");

// objects
$ObjectLocks = $db->fetchAll("SELECT o_id,o_path,o_key,o_locked FROM objects WHERE o_locked IS NOT NULL AND o_locked != ''");
foreach ($ObjectLocks as $lock) {
    $db->insert("tree_locks", array(
        "id" => $lock["o_id"],
        "type" => "object",
        "locked" => $lock["o_locked"]
    ));
}
sendQuery("ALTER TABLE `objects` DROP COLUMN `o_locked`;");




?>