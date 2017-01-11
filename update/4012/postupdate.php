<?php

$db = \Pimcore\Db::get();

$db->query("ALTER TABLE `search_backend_data` DROP INDEX `data`;");
$db->query("ALTER TABLE `search_backend_data` DROP INDEX `properties`;");
