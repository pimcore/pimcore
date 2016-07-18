<?php

// get db connection
$db = \Pimcore\Db::get();

$db->query("INSERT INTO `users_permission_definitions` (`key`) VALUES ('tags_assignment')");
$db->query("INSERT INTO `users_permission_definitions` (`key`) VALUES ('tags_configuration')");
$db->query("INSERT INTO `users_permission_definitions` (`key`) VALUES ('tags_search')");

$db->query("CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(10) unsigned DEFAULT NULL,
  `idPath` varchar(255) DEFAULT NULL,
  `name` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idpath` (`idPath`),
  KEY `parentid` (`parentId`)
) DEFAULT CHARSET=utf8;
");

$db->query("CREATE TABLE `tags_assignment` (
  `tagid` int(10) unsigned NOT NULL DEFAULT '0',
  `cid` int(10) NOT NULL DEFAULT '0',
  `ctype` enum('document','asset','object') NOT NULL,
  PRIMARY KEY (`tagid`,`cid`,`ctype`),
  KEY `ctype` (`ctype`),
  KEY `ctype_cid` (`cid`,`ctype`),
  KEY `tagid` (`tagid`)
) DEFAULT CHARSET=utf8;
");

