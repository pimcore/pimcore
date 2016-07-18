<?php

$db = \Pimcore\Resource::get();
$db->query("ALTER TABLE properties CHANGE COLUMN `cpath` `cpath` varchar(765) CHARACTER SET ascii DEFAULT NULL;");
$db->query("ALTER TABLE recyclebin CHANGE COLUMN `path` `path` varchar(765) DEFAULT NULL;");
$db->query("ALTER TABLE search_backend_data CHANGE COLUMN `fullpath` `fullpath` varchar(765) CHARACTER SET ascii DEFAULT NULL;");

$db->query("ALTER TABLE users_workspaces_asset CHANGE COLUMN `cpath` `cpath` varchar(765) CHARACTER SET ascii DEFAULT NULL;");
$db->query("ALTER TABLE users_workspaces_document CHANGE COLUMN `cpath` `cpath` varchar(765) CHARACTER SET ascii DEFAULT NULL;");
$db->query("ALTER TABLE users_workspaces_object CHANGE COLUMN `cpath` `cpath` varchar(765) CHARACTER SET ascii DEFAULT NULL;");

