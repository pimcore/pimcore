<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->query("CREATE TABLE `targeting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `documentId` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `conditions` longtext,
  `actions` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_documentId` (`documentId`,`name`),
  KEY `documentId` (`documentId`)
) DEFAULT CHARSET=utf8;");

