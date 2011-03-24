<?php

// get db connection
$db = Pimcore_Resource_Mysql::get("database");

$db->exec("CREATE TABLE `translations_admin` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `tid` int(11) unsigned NOT NULL default '0',
  `key` varchar(255) character set utf8 collate utf8_bin NOT NULL default '',
  `language` varchar(10) character set utf8 collate utf8_bin default NULL,
  `text` text character set utf8 collate utf8_bin,
  `date` bigint(20) unsigned default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `tid_language` (`tid`,`language`),
  UNIQUE KEY `key_language` (`key`,`language`),
  KEY `language` (`language`),
  KEY `key` (`key`),
  KEY `tid` (`tid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$db->exec("RENAME TABLE `translations` TO `translations_website`;");

$db->exec("ALTER TABLE `classes` ADD COLUMN `propertyVisibility` text NULL;");
