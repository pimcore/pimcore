<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->query("DROP TABLE IF EXISTS `content_analysis`;");
$db->query("CREATE TABLE `content_analysis` (
  `id` varchar(44) NOT NULL DEFAULT '',
  `host` varchar(255) DEFAULT NULL,
  `site` int(11) DEFAULT NULL,
  `url` varchar(2000) NOT NULL DEFAULT '',
  `type` enum('document','route') DEFAULT NULL,
  `typeReference` int(11) DEFAULT NULL,
  `facebookShares` int(11) DEFAULT NULL,
  `googlePlusOne` int(11) DEFAULT NULL,
  `links` int(5) DEFAULT NULL,
  `linksExternal` int(5) DEFAULT NULL,
  `h1` int(3) DEFAULT NULL,
  `h2` int(3) DEFAULT NULL,
  `h3` int(3) DEFAULT NULL,
  `h4` int(3) DEFAULT NULL,
  `h5` int(3) DEFAULT NULL,
  `h6` int(3) DEFAULT NULL,
  `h1Text` varchar(1000) DEFAULT NULL,
  `imgWithoutAlt` int(3) DEFAULT NULL,
  `imgWithAlt` int(3) DEFAULT NULL,
  `title` varchar(1000) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `urlLength` int(4) DEFAULT NULL,
  `urlParameters` int(2) DEFAULT NULL,
  `microdata` int(3) DEFAULT NULL,
  `opengraph` int(3) DEFAULT NULL,
  `twitter` int(3) DEFAULT NULL,
  `robotsTxtBlocked` tinyint(1) DEFAULT NULL,
  `robotsMetaBlocked` tinyint(1) DEFAULT NULL,
  `lastUpdate` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `host` (`host`),
  KEY `lastUpdate` (`lastUpdate`),
  KEY `site` (`site`)
) DEFAULT CHARSET=utf8;");

$db->query("DROP TABLE IF EXISTS `content_index`;");
$db->query("CREATE TABLE `content_index` (
  `id` varchar(44) NOT NULL DEFAULT '',
  `site` int(11) DEFAULT NULL,
  `url` varchar(2000) NOT NULL DEFAULT '',
  `content` longtext,
  `type` enum('document','route') DEFAULT NULL,
  `typeReference` int(11) DEFAULT NULL,
  `lastUpdate` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lastUpdate` (`lastUpdate`)
) DEFAULT CHARSET=utf8;");

