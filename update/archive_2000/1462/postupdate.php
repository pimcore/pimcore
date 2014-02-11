<?php

// get db connection
$db = Pimcore_Resource::get();


try {
    $db->query("ALTER TABLE `documents` CHANGE COLUMN `type` `type` enum('page','link','snippet','folder','hardlink') NULL DEFAULT NULL;");

    $db->query("
        CREATE TABLE `documents_hardlink` (
          `id` int(11) DEFAULT NULL,
          `sourceId` int(11) DEFAULT NULL,
          `propertiesFromSource` tinyint(1) DEFAULT NULL,
          `inheritedPropertiesFromSource` tinyint(1) DEFAULT NULL,
          `childsFromSource` tinyint(1) DEFAULT NULL,
          UNIQUE KEY `id` (`id`)
        ) DEFAULT CHARSET=utf8;
    ");

} catch (Exception $e) {
    echo $e->getMessage();
}
    
