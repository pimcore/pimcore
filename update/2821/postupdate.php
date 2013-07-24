<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("DROP TABLE IF EXISTS `session`;");
$db->query("CREATE TABLE `session` (
  `id` char(32) NOT NULL DEFAULT '',
  `modified` int(11) DEFAULT NULL,
  `lifetime` int(11) DEFAULT NULL,
  `data` text,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;");
