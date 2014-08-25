<?php

// get db connection
$db = Pimcore_Resource::get();

try{
    $db->query("DROP TABLE IF EXISTS `assets_metadata_predefined`;");

    $db->query("CREATE TABLE `assets_metadata_predefined` (
                      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(255) DEFAULT NULL,
                      `description` text,
                      `language` varchar(255) DEFAULT NULL,
                      `type` enum('input','textarea','asset','document','object','date') DEFAULT NULL,
                      `data` text,
                      `targetSubtype` enum('image', 'text', 'audio', 'video', 'document', 'archive', 'unknown') DEFAULT NULL,
                      `creationDate` bigint(20) unsigned DEFAULT '0',
                      `modificationDate` bigint(20) unsigned DEFAULT '0',
                      PRIMARY KEY (`id`),
                      KEY `name` (`name`),
                      KEY `id` (`id`),
                      KEY `type` (`type`),
                      KEY `language` (`language`),
                      KEY `targetSubtype` (`targetSubtype`)
                    ) DEFAULT CHARSET=utf8;");
} catch (\Exception $e) {
    echo $e->getMessage();
}
