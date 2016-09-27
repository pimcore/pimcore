<?php

$db = \Pimcore\Db::get();
$db->query("ALTER TABLE tags CHANGE COLUMN `name` `name` varchar(255) DEFAULT NULL;");
