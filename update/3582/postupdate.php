<?php

// get db connection
$db = \Pimcore\Db::get();

$db->query("DELETE FROM `users_permission_definitions` WHERE `key`='document_style_editor';");
$db->query("ALTER TABLE documents_page DROP COLUMN `css`;");

\Pimcore\Cache::clearAll();
