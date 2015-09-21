<?php

$db = \Pimcore\Resource::get();
$db->query("ALTER TABLE `assets` ADD COLUMN `hasMetaData` tinyint(1) NOT NULL DEFAULT 0;");
$db->query("UPDATE assets SET hasMetaData = 1 WHERE id IN (SELECT DISTINCT cid FROM assets_metadata)");
