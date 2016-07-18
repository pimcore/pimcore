<?php

$db = \Pimcore\Resource::get();
$db->query("DROP TABLE IF EXISTS `http_error_log`;");

$db->query("CREATE TABLE `http_error_log` (
  `uri` varchar(3000) CHARACTER SET ascii DEFAULT NULL,
  `code` int(3) DEFAULT NULL,
  `parametersGet` longtext,
  `parametersPost` longtext,
  `cookies` longtext,
  `serverVars` longtext,
  `date` bigint(20) DEFAULT NULL,
  `count` bigint(20) DEFAULT NULL,
  KEY (`uri` (765)),
  KEY `code` (`code`),
  KEY `date` (`date`),
  KEY `count` (`count`)
) DEFAULT CHARSET=utf8;");

