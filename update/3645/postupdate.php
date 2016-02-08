<?php

// get db connection
$db = Pimcore\Db::get();

$tables = $db->query("ALTER TABLE documents_elements CHANGE COLUMN `name` `name` varchar(750) CHARACTER SET ascii DEFAULT NULL;");
