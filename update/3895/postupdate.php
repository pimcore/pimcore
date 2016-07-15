<?php

$db = \Pimcore\Db::get();
$db->query("
    ALTER TABLE users ADD COLUMN allowDirtyClose TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;
");
