<?php

// get db connection
$db = Pimcore_Resource::get();

try{
    $db->query("ALTER TABLE `keyvalue_keys` ADD COLUMN `translator` int(11) DEFAULT NULL;");
    $db->query("ALTER TABLE `keyvalue_keys` CHANGE COLUMN `type` `type` enum('bool','number','select','text','translated') NULL DEFAULT NULL;");
} catch (\Exception $e) {

}