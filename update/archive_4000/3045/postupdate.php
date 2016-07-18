<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("CREATE TABLE `email_blacklist` (
  `address` varchar(255) NOT NULL DEFAULT '',
  `creationDate` int(11) unsigned DEFAULT NULL,
  `modificationDate` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`address`)
) DEFAULT CHARSET=utf8;");
