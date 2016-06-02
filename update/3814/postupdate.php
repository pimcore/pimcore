<?php

$db = \Pimcore\Db::get();

$types = \Pimcore\Model\Document::getTypes();
$types[] = "printpage";
$types[] = "printcontainer";
$types = array_unique($types);

$db->query("
     ALTER TABLE documents
     CHANGE type type ENUM('" . implode("', '", $types) . "');
");


// add missing indexes
$db->query("CREATE TABLE IF NOT EXISTS `documents_printpage` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `module` varchar(255) DEFAULT NULL,
  `controller` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `lastGenerated` int(11) DEFAULT NULL,
  `lastGenerateMessage` text CHARACTER SET utf8,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
");
