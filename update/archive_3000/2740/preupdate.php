<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("DROP TABLE IF EXISTS `cache`;");
$db->query("CREATE TABLE `cache` (
  `id` varchar(165) NOT NULL DEFAULT '',
  `data` longtext,
  `mtime` bigint(20) DEFAULT NULL,
  `expire` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;");
