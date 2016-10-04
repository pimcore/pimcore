<?php

$db = \Pimcore\Db::get();

// shouldn't be necessary, since default collation of ascii is ascii_general_ci, but just to be entirely sure
$db->query("ALTER TABLE assets CHANGE COLUMN `path` `path` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL");
$db->query("ALTER TABLE assets CHANGE COLUMN `filename` `filename` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT ''");

$db->query("ALTER TABLE documents CHANGE COLUMN `path` `path` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL");
$db->query("ALTER TABLE documents CHANGE COLUMN `key` `key`  varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT ''");
$db->query("ALTER TABLE documents_elements CHANGE COLUMN `name` `name` varchar(750) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''");

$db->query("ALTER TABLE objects CHANGE COLUMN `o_path` `o_path` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL");
$db->query("ALTER TABLE objects CHANGE COLUMN `o_key` `o_key` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci default ''");

$db->query("ALTER TABLE properties CHANGE COLUMN `cpath` `cpath` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL");
$db->query("ALTER TABLE search_backend_data CHANGE COLUMN `fullpath` `fullpath` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL");
$db->query("ALTER TABLE users_workspaces_asset CHANGE COLUMN `cpath` `cpath` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL");
$db->query("ALTER TABLE users_workspaces_document CHANGE COLUMN `cpath` `cpath` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL");
$db->query("ALTER TABLE users_workspaces_object CHANGE COLUMN `cpath` `cpath` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL");
