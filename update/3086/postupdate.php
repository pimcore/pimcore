<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("CREATE TABLE `assets_metadata` (
  `cid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `language` varchar(255) DEFAULT NULL,
  `type` enum('input','textarea') DEFAULT NULL,
  `data` text,
  KEY `cid` (`cid`)
) DEFAULT CHARSET=utf8;");
