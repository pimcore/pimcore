<?php

// get db connection
$db = Pimcore\Db::get();

$tables = $db->query("CREATE TABLE `documents_translations` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `sourceId` int(11) unsigned NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`sourceId`,`language`),
  KEY `id` (`id`),
  KEY `sourceId` (`sourceId`),
  KEY `language` (`language`)
) DEFAULT CHARSET=utf8;");

$tables = $db->fetchAll("SHOW TABLES LIKE 'object_classificationstore_groups_%'");

foreach ($tables as $table) {
    $t = current($table);

    // migrate the quantity value records
    $db->query("ALTER TABLE `" . $t . "`
        ADD INDEX `o_id` (`o_id`);
    ");

}


