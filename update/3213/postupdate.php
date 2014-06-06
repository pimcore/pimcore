<?php

// get db connection
$db = Pimcore_Resource::get();

try{
    $db->query("ALTER TABLE `assets_metadata`
	    CHANGE COLUMN `type` `type` ENUM('input','textarea','asset','document','object','date') DEFAULT NULL AFTER `language`;");
} catch (\Exception $e) {
    echo $e->getMessage();
}
