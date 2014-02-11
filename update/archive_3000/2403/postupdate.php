<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->query("CREATE TABLE `content_index` (
  `id` varchar(32) NOT NULL DEFAULT '',
  `site` int(11) DEFAULT NULL,
  `url` varchar(2000) NOT NULL DEFAULT '',
  `content` longtext,
  `type` enum('document','route') DEFAULT NULL,
  `typeReference` int(11) DEFAULT NULL,
  `lastUpdate` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;");
