CREATE TABLE `translations` (
  `key` varchar(255) NOT NULL default '',
  `language` varchar(2) character set utf8 collate utf8_bin default NULL,
  `text` text,
  KEY `language` (`language`),
  KEY `key` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


INSERT INTO `users_permission_definitions` SET `key`='translations', `translation`='permission_translations';