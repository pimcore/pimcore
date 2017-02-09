<?php

$db = \Pimcore\Db::get();
$db->query("ALTER TABLE versions ADD stackTrace text AFTER note;");

