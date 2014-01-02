<?php

// get db connection
$db = Pimcore_Resource::get();
$db->update("content_index", array("content" => ""));
$db->query("ALTER TABLE `content_index` CHANGE COLUMN `content` `content` longblob NULL;");
