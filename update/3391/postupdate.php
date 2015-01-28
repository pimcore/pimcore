<?php

$db = \Pimcore\Resource::get();
$db->query("ALTER TABLE documents CHANGE COLUMN `path` `path` varchar(765) CHARACTER SET ascii DEFAULT NULL;");
$db->query("ALTER TABLE assets CHANGE COLUMN `path` `path` varchar(765) CHARACTER SET ascii DEFAULT NULL;");
$db->query("ALTER TABLE objects CHANGE COLUMN `o_path` `o_path` varchar(765) CHARACTER SET ascii DEFAULT NULL;");

