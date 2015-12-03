<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->query("DROP TABLE IF EXISTS `cache_tags`;");

$db->query("CREATE TABLE `cache_tags` (
  `id` varchar(165) NOT NULL DEFAULT '',
  `tag` varchar(165) NULL DEFAULT NULL,
  PRIMARY KEY (`id`,`tag`),
  INDEX `id` (`id`),
  INDEX `tag` (`tag`)
) ENGINE=MEMORY;");


