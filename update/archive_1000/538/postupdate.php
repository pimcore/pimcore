<?php


// get db connection
$db = Pimcore_Resource_Mysql::get();
$db->getConnection()->exec("DROP TABLE IF EXISTS `recyclebin`");
$db->getConnection()->exec("CREATE TABLE `recyclebin` (
  `id` int(11) NOT NULL auto_increment,
  `type` varchar(20) default NULL,
  `subtype` varchar(20) default NULL,
  `path` varchar(255) default NULL,
  `amount` int(3) default NULL,
  `date` bigint(20) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

?>