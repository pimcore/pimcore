
DROP TABLE IF EXISTS `assets`;
CREATE TABLE `assets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(11) unsigned DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `filename` varchar(255) DEFAULT '',
  `path` varchar(255) DEFAULT NULL,
  `mimetype` varchar(255) DEFAULT NULL,
  `creationDate` bigint(20) unsigned DEFAULT NULL,
  `modificationDate` bigint(20) unsigned DEFAULT NULL,
  `userOwner` int(11) unsigned DEFAULT NULL,
  `userModification` int(11) unsigned DEFAULT NULL,
  `customSettings` text,
  `locked` enum('self','propagate') default NULL,
  PRIMARY KEY (`id`),
  KEY `parentId` (`parentId`),
  KEY `filename` (`filename`),
  KEY `path` (`path`),
  KEY `locked` (`locked`)
) AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `assets_permissions`;
CREATE TABLE `assets_permissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(11) unsigned DEFAULT NULL,
  `cpath` varchar(255) DEFAULT NULL,
  `userId` int(11) unsigned DEFAULT NULL,
  `list` tinyint(1) DEFAULT '0',
  `view` tinyint(1) DEFAULT '0',
  `publish` tinyint(1) DEFAULT '0',
  `delete` tinyint(1) DEFAULT '0',
  `rename` tinyint(1) DEFAULT '0',
  `create` tinyint(1) DEFAULT '0',
  `permissions` tinyint(1) DEFAULT '0',
  `settings` tinyint(1) DEFAULT '0',
  `versions` tinyint(1) DEFAULT '0',
  `properties` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `cpath` (`cpath`),
  KEY `userId` (`userId`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `cache_tags`;
CREATE TABLE `cache_tags` (
  `id` varchar(80) NOT NULL DEFAULT '',
  `tag` varchar(80) NULL DEFAULT NULL,
  PRIMARY KEY (`id`(80),`tag`(80)),
  INDEX `id` (`id`(80)),
  INDEX `tag` (`tag`(80))
) ENGINE=MEMORY;

DROP TABLE IF EXISTS `classes` ;
CREATE TABLE `classes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `creationDate` bigint(20) unsigned DEFAULT NULL,
  `modificationDate` bigint(20) unsigned DEFAULT NULL,
  `userOwner` int(11) unsigned DEFAULT NULL,
  `userModification` int(11) unsigned DEFAULT NULL,
  `allowInherit` tinyint(1) unsigned DEFAULT '0',
  `allowVariants` tinyint(1) unsigned DEFAULT '0',
  `parentClass` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `propertyVisibility` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `dependencies` ;
CREATE TABLE `dependencies` (
  `sourcetype` enum('document','asset','object') NOT NULL DEFAULT 'document',
  `sourceid` int(11) unsigned NOT NULL DEFAULT '0',
  `targettype` enum('document','asset','object') NOT NULL DEFAULT 'document',
  `targetid` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`sourcetype`,`sourceid`,`targetid`,`targettype`),
  KEY `sourceid` (`sourceid`),
  KEY `targetid` (`targetid`),
  KEY `sourcetype` (`sourcetype`),
  KEY `targettype` (`targettype`)
) DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `documents` ;
CREATE TABLE `documents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(11) unsigned DEFAULT NULL,
  `type` enum('page','link','snippet','folder','hardlink') DEFAULT NULL,
  `key` varchar(255) DEFAULT '',
  `path` varchar(255) DEFAULT NULL,
  `index` int(11) unsigned DEFAULT '999999',
  `published` tinyint(1) unsigned DEFAULT '1',
  `creationDate` bigint(20) unsigned DEFAULT NULL,
  `modificationDate` bigint(20) unsigned DEFAULT NULL,
  `userOwner` int(11) unsigned DEFAULT NULL,
  `userModification` int(11) unsigned DEFAULT NULL,
  `locked` enum('self','propagate') default NULL,
  PRIMARY KEY (`id`),
  KEY `parentId` (`parentId`),
  KEY `key` (`key`),
  KEY `path` (`path`),
  KEY `published` (`published`),
  KEY `locked` (`locked`)
) AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `documents_doctypes`;
CREATE TABLE `documents_doctypes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `controller` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `type` enum('page','snippet') DEFAULT NULL,
  `priority` int(3) DEFAULT '0',
  PRIMARY KEY  (`id`),
  KEY `priority` (`priority`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `documents_elements`;
CREATE TABLE `documents_elements` (
  `documentId` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(50) DEFAULT NULL,
  `data` longtext,
  PRIMARY KEY (`documentId`,`name`),
  KEY `documentId` (`documentId`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `documents_link`;
CREATE TABLE `documents_link` (
  `id` int(11) unsigned NOT NULL default '0',
  `internalType` enum('document','asset') default NULL,
  `internal` int(11) unsigned default NULL,
  `direct` varchar(255) default NULL,
  `linktype` enum('direct','internal') default NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `documents_hardlink`;
CREATE TABLE `documents_hardlink` (
  `id` int(11) DEFAULT NULL,
  `sourceId` int(11) DEFAULT NULL,
  `propertiesFromSource` tinyint(1) DEFAULT NULL,
  `inheritedPropertiesFromSource` tinyint(1) DEFAULT NULL,
  `childsFromSource` tinyint(1) DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `documents_page` ;
CREATE TABLE `documents_page` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `controller` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `documents_permissions`;
CREATE TABLE `documents_permissions` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cid` int(11) unsigned default NULL,
  `cpath` varchar(255) default NULL,
  `userId` int(11) unsigned default NULL,
  `list` tinyint(1) unsigned default '0',
  `view` tinyint(1) unsigned default '0',
  `save` tinyint(1) unsigned default '0',
  `publish` tinyint(1) unsigned default '0',
  `unpublish` tinyint(1) unsigned default '0',
  `delete` tinyint(1) unsigned default '0',
  `rename` tinyint(1) unsigned default '0',
  `create` tinyint(1) unsigned default '0',
  `permissions` tinyint(1) unsigned default '0',
  `settings` tinyint(1) unsigned default '0',
  `versions` tinyint(1) unsigned default '0',
  `properties` tinyint(1) unsigned default '0',
  PRIMARY KEY  (`id`),
  KEY `cid` (`cid`),
  KEY `cpath` (`cpath`),
  KEY `userId` (`userId`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `documents_snippet`;
CREATE TABLE `documents_snippet` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `controller` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `edit_lock`;
CREATE TABLE `edit_lock` (
  `id` int(11) NOT NULL auto_increment,
  `cid` int(11) unsigned NOT NULL default '0',
  `ctype` enum('document','asset','object') default NULL,
  `userId` int(11) unsigned NOT NULL default '0',
  `sessionId` varchar(255) default NULL,
  `date` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `cid` (`cid`),
  KEY `ctype` (`ctype`),
  KEY `cidtype` (`cid`,`ctype`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `glossary`;
CREATE TABLE `glossary` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `language` varchar(2) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `abbr` varchar(255) DEFAULT NULL,
  `acronym` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `language` (`language`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `objects`;
CREATE TABLE `objects` (
  `o_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `o_parentId` int(11) unsigned DEFAULT NULL,
  `o_type` enum('object','folder','variant') DEFAULT NULL,
  `o_key` varchar(255) default '',
  `o_path` varchar(255) DEFAULT NULL,
  `o_index` int(11) unsigned DEFAULT '0',
  `o_published` tinyint(1) unsigned DEFAULT '1',
  `o_creationDate` bigint(20) unsigned DEFAULT NULL,
  `o_modificationDate` bigint(20) unsigned DEFAULT NULL,
  `o_userOwner` int(11) unsigned DEFAULT NULL,
  `o_userModification` int(11) unsigned DEFAULT NULL,
  `o_classId` int(11) unsigned DEFAULT NULL,
  `o_className` varchar(255) DEFAULT NULL,
  `o_locked` enum('self','propagate') default NULL,
  PRIMARY KEY (`o_id`),
  KEY `key` (`o_key`),
  KEY `path` (`o_path`),
  KEY `type` (`o_type`),
  KEY `published` (`o_published`),
  KEY `parentId` (`o_parentId`),
  KEY `o_locked` (`o_locked`)
) AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `objects_permissions`;
CREATE TABLE `objects_permissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(11) unsigned DEFAULT NULL,
  `cpath` varchar(255) DEFAULT NULL,
  `userId` int(11) unsigned DEFAULT NULL,
  `list` tinyint(1) unsigned DEFAULT '0',
  `view` tinyint(1) unsigned DEFAULT '0',
  `save` tinyint(1) unsigned DEFAULT '0',
  `publish` tinyint(1) unsigned DEFAULT '0',
  `unpublish` tinyint(1) unsigned DEFAULT '0',
  `delete` tinyint(1) unsigned DEFAULT '0',
  `rename` tinyint(1) unsigned DEFAULT '0',
  `create` tinyint(1) unsigned DEFAULT '0',
  `permissions` tinyint(1) unsigned DEFAULT '0',
  `settings` tinyint(1) unsigned DEFAULT '0',
  `versions` tinyint(1) unsigned DEFAULT '0',
  `properties` tinyint(1) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `object_user` (`cid`,`userId`),
  KEY `cid` (`cid`),
  KEY `cpath` (`cpath`),
  KEY `userId` (`userId`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `properties`;
CREATE TABLE `properties` (
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `ctype` enum('document','asset','object') NOT NULL DEFAULT 'document',
  `cpath` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `type` enum('text','date','document','asset','object','bool','select') DEFAULT NULL,
  `data` text,
  `inheritable` tinyint(1) unsigned DEFAULT '1',
  PRIMARY KEY (`cid`,`ctype`,`name`),
  KEY `cpath` (`cpath`),
  KEY `inheritable` (`inheritable`),
  KEY `ctype` (`ctype`),
  KEY `cid` (`cid`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `properties_predefined`;
CREATE TABLE `properties_predefined` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '',
  `description` text,
  `key` varchar(255) DEFAULT NULL,
  `type` enum('text','document','asset','bool','select','object') DEFAULT NULL,
  `data` text,
  `config` text,
  `ctype` enum('document','asset','object') DEFAULT NULL,
  `inheritable` tinyint(1) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `id` (`id`),
  KEY `key` (`key`),
  KEY `type` (`type`),
  KEY `ctype` (`ctype`),
  KEY `inheritable` (`inheritable`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `recyclebin`;
CREATE TABLE `recyclebin` (
  `id` int(11) NOT NULL auto_increment,
  `type` varchar(20) default NULL,
  `subtype` varchar(20) default NULL,
  `path` varchar(255) default NULL,
  `amount` int(3) default NULL,
  `date` bigint(20) default NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `redirects`;
CREATE TABLE `redirects` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(255) DEFAULT NULL,
  `target` varchar(255) DEFAULT NULL,
  `statusCode` varchar(3) DEFAULT NULL,
  `priority` int(2) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `priority` (`priority`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `schedule_tasks`;
CREATE TABLE `schedule_tasks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(11) unsigned DEFAULT NULL,
  `ctype` enum('document','asset','object') DEFAULT NULL,
  `date` bigint(20) unsigned DEFAULT NULL,
  `action` enum('publish','unpublish','delete','publish-version') DEFAULT NULL,
  `version` bigint(20) unsigned DEFAULT NULL,
  `active` tinyint(1) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `ctype` (`ctype`),
  KEY `active` (`active`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `sites`;
CREATE TABLE `sites` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `domains` text,
  `rootId` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rootId` (`rootId`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS  `staticroutes`;
CREATE TABLE `staticroutes` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  `pattern` varchar(255) default NULL,
  `reverse` varchar(255) default NULL,
  `module` varchar(255) default NULL,
  `controller` varchar(255) default NULL,
  `action` varchar(255) default NULL,
  `variables` varchar(255) default NULL,
  `defaults` varchar(255) default NULL,
  `priority` int(3) DEFAULT '0',
  PRIMARY KEY  (`id`),
  KEY `priority` (`priority`),
  KEY `name` (`name`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `thumbnails`;
CREATE TABLE `thumbnails` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `description` text,
  `width` int(11) unsigned DEFAULT NULL,
  `height` int(11) unsigned DEFAULT NULL,
  `aspectratio` tinyint(1) unsigned DEFAULT '0',
  `cover` tinyint(1) unsigned NOT NULL default '0',
  `contain` tinyint(1) unsigned NOT NULL default '0',
  `interlace` tinyint(1) unsigned DEFAULT '0',
  `quality` int(3) DEFAULT NULL,
  `format` enum('PNG','JPEG','GIF','SOURCE') DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `translations_website`;
CREATE TABLE `translations_website` (
  `key` varchar(255) NOT NULL DEFAULT '',
  `language` varchar(10) NOT NULL DEFAULT '',
  `text` text,
  `date` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`key`,`language`),
  KEY `language` (`language`),
  KEY `key` (`key`)
) DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `translations_admin`;
CREATE TABLE `translations_admin` (
  `key` varchar(255) NOT NULL DEFAULT '',
  `language` varchar(10) NOT NULL DEFAULT '',
  `text` text,
  `date` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`key`,`language`),
  KEY `language` (`language`),
  KEY `key` (`key`)
) DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(11) unsigned DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `language` varchar(2) DEFAULT NULL,
  `admin` tinyint(1) unsigned DEFAULT '0',
  `hasCredentials` tinyint(1) unsigned DEFAULT '1',
  `active` tinyint(1) unsigned DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `parentId` (`parentId`)
) DEFAULT CHARSET=utf8;

INSERT INTO  `users` (parentId,username,admin,hasCredentials,active) values (0,'system',1,1,1);
UPDATE  `users` SET id = 0 WHERE username = 'system';
ALTER TABLE `users` AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `users_permission_definitions`;
CREATE TABLE `users_permission_definitions` (
  `key` varchar(50) NOT NULL DEFAULT '',
  `translation` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`key`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `users_permissions`;
CREATE TABLE `users_permissions` (
  `userId` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`userId`,`name`)
) DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `versions`;
CREATE TABLE `versions` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `cid` int(11) unsigned default NULL,
  `ctype` enum('document','asset','object') default NULL,
  `userId` int(11) unsigned default NULL,
  `note` text,
  `date` bigint(1) unsigned default NULL,
  `public` tinyint(1) unsigned NOT NULL default '0',
  `serialized` tinyint(1) unsigned default '0',
  PRIMARY KEY  (`id`),
  KEY `cid` (`cid`),
  KEY `ctype` (`ctype`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `sanitycheck`;
CREATE TABLE `sanitycheck` (
  `id` int(11) unsigned NOT NULL,
  `type` enum('document','asset','object') NOT NULL,
  PRIMARY KEY  (`id`,`type`)
) DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `search_backend_data`;
CREATE TABLE `search_backend_data` (
  `id` int(11) NOT NULL,
  `fullpath` varchar(510) DEFAULT NULL,
  `maintype` varchar(8) NOT NULL DEFAULT '',
  `type` varchar(20) DEFAULT NULL,
  `subtype` varchar(255) DEFAULT NULL,
  `published` bigint(20) DEFAULT NULL,
  `creationDate` bigint(20) DEFAULT NULL,
  `modificationDate` bigint(20) DEFAULT NULL,
  `userOwner` int(11) DEFAULT NULL,
  `userModification` int(11) DEFAULT NULL,
  `data` longtext,
  `fieldcollectiondata` longtext,
  `localizeddata` longtext,
  `properties` text,
  PRIMARY KEY (`id`,`maintype`),
  KEY `id` (`id`),
  KEY `fullpath` (`fullpath`),
  KEY `maintype` (`maintype`),
  KEY `type` (`type`),
  KEY `subtype` (`subtype`),
  KEY `published` (`published`),
  FULLTEXT KEY `data` (`data`),
  FULLTEXT KEY `fieldcollectiondata` (`fieldcollectiondata`),
  FULLTEXT KEY `localizeddata` (`localizeddata`),
  FULLTEXT KEY `properties` (`properties`),
  FULLTEXT KEY `fulltext` (`data`,`fieldcollectiondata`,`localizeddata`,`properties`,`fullpath`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;




/* ------ DON'T REMOVE OR MODIFY THE FOLLOWING COMMENT, IT IS REQUIRED FOR BACKUPS ------ */
/* ------ INSERT_DATA ------ */

/*
INSERT INTO `assets` VALUES (1,0,'folder','','/',NULL,0,0,1,1,NULL,NULL);
INSERT INTO `documents` VALUES (1,0,'page','','/',999999,1,0,0,1,1,NULL);
INSERT INTO `documents_page` VALUES (1,'','','','','','');
INSERT INTO `objects` VALUES (1,0,'folder','','/',0,1,0,0,1,1,NULL,NULL,NULL);

INSERT INTO `users_permission_definitions` VALUES ('assets','permission_assets');
INSERT INTO `users_permission_definitions` VALUES ('classes','permission_classes');
INSERT INTO `users_permission_definitions` VALUES ('clear_cache','permission_clear_cache');
INSERT INTO `users_permission_definitions` VALUES ('clear_temp_files','permission_clear_temp_files');
INSERT INTO `users_permission_definitions` VALUES ('document_types','permission_document_types');
INSERT INTO `users_permission_definitions` VALUES ('documents','permission_documents');
INSERT INTO `users_permission_definitions` VALUES ('objects','permission_objects');
INSERT INTO `users_permission_definitions` VALUES ('plugins','permission_plugins');
INSERT INTO `users_permission_definitions` VALUES ('predefined_properties','permission_predefined_properties');
INSERT INTO `users_permission_definitions` VALUES ('routes','permission_routes');
INSERT INTO `users_permission_definitions` VALUES ('seemode','permission_seemode');
INSERT INTO `users_permission_definitions` VALUES ('system_settings','permission_system_settings');
INSERT INTO `users_permission_definitions` VALUES ('thumbnails','permission_thumbnails');
INSERT INTO `users_permission_definitions` VALUES ('translations','permission_translations');
INSERT INTO `users_permission_definitions` VALUES ('users','permission_users');
INSERT INTO `users_permission_definitions` VALUES ('update','permissions_update');
INSERT INTO `users_permission_definitions` VALUES ('redirects','permissions_redirects');
INSERT INTO `users_permission_definitions` VALUES ('glossary','permissions_glossary');
INSERT INTO `users_permission_definitions` VALUES ('forms','permission_forms');
INSERT INTO `users_permission_definitions` VALUES ('reports','permissions_reports_marketing');

*/
