<?php

$db = \Pimcore\Db::get();
$db->query("
CREATE TABLE `element_workflow_state` (
  `cid` int(10) NOT NULL DEFAULT '0',
  `ctype` enum('document','asset','object') NOT NULL,
  `state` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`cid`, `ctype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
;");
