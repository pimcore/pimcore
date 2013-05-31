<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("RENAME TABLE `targeting` TO `targeting_rules`;");

$db->query("DROP TABLE IF EXISTS `targeting_personas`;");

$db->query("CREATE TABLE `targeting_personas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `conditions` longtext,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;");

