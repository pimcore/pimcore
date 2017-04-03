
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `application_logs`;
CREATE TABLE `application_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pid` INT(11) NULL DEFAULT NULL,
  `timestamp` datetime NOT NULL,
  `message` varchar(1024) DEFAULT NULL,
  `priority` ENUM('emergency','alert','critical','error','warning','notice','info','debug') DEFAULT NULL,
  `fileobject` varchar(1024) DEFAULT NULL,
  `info` varchar(1024) DEFAULT NULL,
  `component` varchar(190) DEFAULT NULL,
  `source` varchar(190) DEFAULT NULL,
  `relatedobject` bigint(20) DEFAULT NULL,
  `relatedobjecttype` enum('object','document','asset') DEFAULT NULL,
  `maintenanceChecked` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `component` (`component`),
  KEY `timestamp` (`timestamp`),
  KEY `relatedobject` (`relatedobject`),
  KEY `priority` (`priority`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `assets`;
CREATE TABLE `assets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(11) unsigned DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT '',
  `path` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL, /* path in ascii using the full key length of 765 bytes (PIMCORE-2654) */
  `mimetype` varchar(190) DEFAULT NULL,
  `creationDate` bigint(20) unsigned DEFAULT NULL,
  `modificationDate` bigint(20) unsigned DEFAULT NULL,
  `userOwner` int(11) unsigned DEFAULT NULL,
  `userModification` int(11) unsigned DEFAULT NULL,
  `customSettings` text,
  `hasMetaData` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fullpath` (`path`,`filename`),
  KEY `parentId` (`parentId`),
  KEY `filename` (`filename`),
  KEY `path` (`path`),
  KEY `modificationDate` (`modificationDate`)
) AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `assets_metadata`;
CREATE TABLE `assets_metadata` (
  `cid` int(11) DEFAULT NULL,
  `name` varchar(190) DEFAULT NULL,
  `language` varchar(190) DEFAULT NULL,
  `type` ENUM('input','textarea','asset','document','object','date','select','checkbox') DEFAULT NULL,
  `data` text,
  KEY `cid` (`cid`),
	INDEX `name` (`name`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `id` varchar(165) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  `data` longblob,
  `mtime` bigint(20) DEFAULT NULL,
  `expire` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `cache_tags`;
CREATE TABLE `cache_tags` (
  `id` varchar(165) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  `tag` varchar(165) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`,`tag`),
  INDEX `id` (`id`),
  INDEX `tag` (`tag`)
) ENGINE=MEMORY;

DROP TABLE IF EXISTS `classes` ;
CREATE TABLE `classes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(190) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `custom_layouts` ;
CREATE TABLE `custom_layouts` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`classId` INT(11) UNSIGNED NOT NULL,
	`name` VARCHAR(190) NULL DEFAULT NULL,
	`description` TEXT NULL,
	`creationDate` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`modificationDate` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`userOwner` INT(11) UNSIGNED NULL DEFAULT NULL,
	`userModification` INT(11) UNSIGNED NULL DEFAULT NULL,
	`default` tinyint(4) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE INDEX `name` (`name`, `classId`)
) DEFAULT CHARSET=utf8mb4;

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
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents` ;
CREATE TABLE `documents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(11) unsigned DEFAULT NULL,
  `type` enum('page','link','snippet','folder','hardlink','email','newsletter','printpage','printcontainer') DEFAULT NULL,
  `key` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT '',
  `path` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL, /* path in ascii using the full key length of 765 bytes (PIMCORE-2654) */
  `index` int(11) unsigned DEFAULT '0',
  `published` tinyint(1) unsigned DEFAULT '1',
  `creationDate` bigint(20) unsigned DEFAULT NULL,
  `modificationDate` bigint(20) unsigned DEFAULT NULL,
  `userOwner` int(11) unsigned DEFAULT NULL,
  `userModification` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fullpath` (`path`,`key`),
  KEY `parentId` (`parentId`),
  KEY `key` (`key`),
  KEY `path` (`path`),
  KEY `published` (`published`),
  KEY `modificationDate` (`modificationDate`)
) AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_elements`;
CREATE TABLE `documents_elements` (
  `documentId` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(750) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  `type` varchar(50) DEFAULT NULL,
  `data` longtext,
  PRIMARY KEY (`documentId`,`name`),
  KEY `documentId` (`documentId`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_email`;
CREATE TABLE `documents_email` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `module` varchar(255) DEFAULT NULL,
  `controller` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `to` varchar(255) DEFAULT NULL,
  `from` varchar(255) DEFAULT NULL,
  `cc` varchar(255) DEFAULT NULL,
  `bcc` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_newsletter`;
CREATE TABLE `documents_newsletter` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `module` varchar(255) DEFAULT NULL,
  `controller` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `from` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `trackingParameterSource` varchar(255) DEFAULT NULL,
  `trackingParameterMedium` varchar(255) DEFAULT NULL,
  `trackingParameterName` varchar(255) DEFAULT NULL,
  `enableTrackingParameters` tinyint(1) unsigned DEFAULT NULL,
  `sendingMode` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_hardlink`;
CREATE TABLE `documents_hardlink` (
  `id` int(11) unsigned NOT NULL default '0',
  `sourceId` int(11) DEFAULT NULL,
  `propertiesFromSource` tinyint(1) DEFAULT NULL,
  `childsFromSource` tinyint(1) DEFAULT NULL,
  PRIMARY KEY `id` (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_link`;
CREATE TABLE `documents_link` (
  `id` int(11) unsigned NOT NULL default '0',
  `internalType` enum('document','asset') default NULL,
  `internal` int(11) unsigned default NULL,
  `direct` varchar(1000) default NULL,
  `linktype` enum('direct','internal') default NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_page` ;
CREATE TABLE `documents_page` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `module` varchar(255) DEFAULT NULL,
  `controller` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `metaData` text,
  `prettyUrl` varchar(190) DEFAULT NULL,
  `contentMasterDocumentId` int(11) DEFAULT NULL,
  `personas` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prettyUrl` (`prettyUrl`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_snippet`;
CREATE TABLE `documents_snippet` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `module` varchar(255) DEFAULT NULL,
  `controller` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `contentMasterDocumentId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_translations`;
CREATE TABLE `documents_translations` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `sourceId` int(11) unsigned NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`sourceId`,`language`),
  KEY `id` (`id`),
  KEY `sourceId` (`sourceId`),
  KEY `language` (`language`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_printpage`;
CREATE TABLE `documents_printpage` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `module` varchar(255) DEFAULT NULL,
  `controller` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `lastGenerated` int(11) DEFAULT NULL,
  `lastGenerateMessage` text,
  `contentMasterDocumentId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;


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
) DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `email_blacklist`;
CREATE TABLE `email_blacklist` (
  `address` varchar(190) NOT NULL DEFAULT '',
  `creationDate` int(11) unsigned DEFAULT NULL,
  `modificationDate` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`address`)
) DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `email_log`;
CREATE TABLE `email_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `documentId` int(11) DEFAULT NULL,
  `requestUri` varchar(500) DEFAULT NULL,
  `params` text,
  `from` varchar(500) DEFAULT NULL,
  `to` longtext,
  `cc` longtext,
  `bcc` longtext,
  `sentDate` bigint(20) DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `glossary`;
CREATE TABLE `glossary` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `language` varchar(10) DEFAULT NULL,
  `casesensitive` tinyint(1) DEFAULT NULL,
  `exactmatch` tinyint(1) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `abbr` varchar(255) DEFAULT NULL,
  `acronym` varchar(255) DEFAULT NULL,
  `site` int(11) unsigned DEFAULT NULL,
  `creationDate` bigint(20) unsigned DEFAULT '0',
  `modificationDate` bigint(20) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `language` (`language`),
  KEY `site` (`site`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `http_error_log`;
CREATE TABLE `http_error_log` (
  `uri` varchar(3000) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
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
) DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `keyvalue_keys`;
DROP TABLE IF EXISTS `keyvalue_groups`;
CREATE TABLE `keyvalue_groups` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `description` VARCHAR(255),
    `creationDate` bigint(20) unsigned DEFAULT '0',
    `modificationDate` bigint(20) unsigned DEFAULT '0',
    PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `keyvalue_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `type` enum('bool','number','select','text','translated','translatedSelect','range') DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `possiblevalues` text,
  `group` int(11) DEFAULT NULL,
  `creationDate` bigint(20) unsigned DEFAULT '0',
  `modificationDate` bigint(20) unsigned DEFAULT '0',
  `translator` int(11) DEFAULT NULL,
	`mandatory` TINYINT(1) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group` (`group`),
  CONSTRAINT `keyvalue_keys_ibfk_1` FOREIGN KEY (`group`) REFERENCES `keyvalue_groups` (`id`) ON DELETE SET NULL
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `keyvalue_translator_configuration`;
CREATE TABLE `keyvalue_translator_configuration` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NULL DEFAULT NULL,
  `translator` VARCHAR(200) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `locks`;
CREATE TABLE `locks` (
  `id` varchar(150) NOT NULL DEFAULT '',
  `date` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `notes`;
CREATE TABLE `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `cid` int(11) DEFAULT NULL,
  `ctype` enum('asset','document','object') DEFAULT NULL,
  `date` int(11) DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` longtext,
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `ctype` (`ctype`),
  KEY `date` (`date`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `notes_data`;
CREATE TABLE `notes_data` (
  `id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `type` enum('text','date','document','asset','object','bool') DEFAULT NULL,
  `data` text,
  KEY `id` (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `objects`;
CREATE TABLE `objects` (
  `o_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `o_parentId` int(11) unsigned DEFAULT NULL,
  `o_type` enum('object','folder','variant') DEFAULT NULL,
  `o_key` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci default '',
  `o_path` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL, /* path in ascii using the full key length of 765 bytes (PIMCORE-2654) */
  `o_index` int(11) unsigned DEFAULT '0',
  `o_published` tinyint(1) unsigned DEFAULT '1',
  `o_creationDate` bigint(20) unsigned DEFAULT NULL,
  `o_modificationDate` bigint(20) unsigned DEFAULT NULL,
  `o_userOwner` int(11) unsigned DEFAULT NULL,
  `o_userModification` int(11) unsigned DEFAULT NULL,
  `o_classId` int(11) unsigned DEFAULT NULL,
  `o_className` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`o_id`),
  UNIQUE KEY `fullpath` (`o_path`,`o_key`),
  KEY `key` (`o_key`),
  KEY `path` (`o_path`),
  KEY `published` (`o_published`),
  KEY `parentId` (`o_parentId`),
  KEY `type` (`o_type`),
  KEY `o_modificationDate` (`o_modificationDate`)
) AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `properties`;
CREATE TABLE `properties` (
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `ctype` enum('document','asset','object') NOT NULL DEFAULT 'document',
  `cpath` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL, /* path in ascii using the full key length of 765 bytes (PIMCORE-2654) */
  `name` varchar(190) NOT NULL DEFAULT '',
  `type` enum('text','document','asset','object','bool','select') DEFAULT NULL,
  `data` text,
  `inheritable` tinyint(1) unsigned DEFAULT '1',
  PRIMARY KEY (`cid`,`ctype`,`name`),
  KEY `cpath` (`cpath`),
  KEY `inheritable` (`inheritable`),
  KEY `ctype` (`ctype`),
  KEY `cid` (`cid`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `recyclebin`;
CREATE TABLE `recyclebin` (
  `id` int(11) NOT NULL auto_increment,
  `type` varchar(20) default NULL,
  `subtype` varchar(20) default NULL,
  `path` varchar(765) default NULL,
  `amount` int(3) default NULL,
  `date` bigint(20) default NULL,
  `deletedby` varchar(50) DEFAULT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `redirects`;
CREATE TABLE `redirects` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(255) DEFAULT NULL,
  `sourceEntireUrl` tinyint(1) DEFAULT NULL,
  `sourceSite` int(11) DEFAULT NULL,
  `passThroughParameters` tinyint(1) DEFAULT NULL,
  `target` varchar(255) DEFAULT NULL,
  `targetSite` int(11) DEFAULT NULL,
  `statusCode` varchar(3) DEFAULT NULL,
  `priority` int(2) DEFAULT '0',
  `active` tinyint(1) DEFAULT NULL,
  `expiry` bigint(20) DEFAULT NULL,
  `creationDate` bigint(20) unsigned DEFAULT '0',
  `modificationDate` bigint(20) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `priority` (`priority`),
  KEY `active` (`active`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `sanitycheck`;
CREATE TABLE `sanitycheck` (
  `id` int(11) unsigned NOT NULL,
  `type` enum('document','asset','object') NOT NULL,
  PRIMARY KEY  (`id`,`type`)
) DEFAULT CHARSET=utf8mb4;

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
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `search_backend_data`;
CREATE TABLE `search_backend_data` (
  `id` int(11) NOT NULL,
  `fullpath` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL, /* path in ascii using the full key length of 765 bytes (PIMCORE-2654) */
  `maintype` varchar(8) NOT NULL DEFAULT '',
  `type` varchar(20) DEFAULT NULL,
  `subtype` varchar(255) DEFAULT NULL,
  `published` bigint(20) DEFAULT NULL,
  `creationDate` bigint(20) DEFAULT NULL,
  `modificationDate` bigint(20) DEFAULT NULL,
  `userOwner` int(11) DEFAULT NULL,
  `userModification` int(11) DEFAULT NULL,
  `data` longtext,
  `properties` text,
  PRIMARY KEY (`id`,`maintype`),
  KEY `id` (`id`),
  KEY `fullpath` (`fullpath`),
  KEY `maintype` (`maintype`),
  KEY `type` (`type`),
  KEY `subtype` (`subtype`),
  KEY `published` (`published`),
  FULLTEXT KEY `fulltext` (`data`,`properties`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/* Engine is changed to InnoDB (if available) in Pimcore\Model\Tool\Setup\Resource::database() - not here because all comments are removed */

DROP TABLE IF EXISTS `sites`;
CREATE TABLE `sites` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mainDomain` varchar(255) DEFAULT NULL,
  `domains` text,
  `rootId` int(11) unsigned DEFAULT NULL,
  `errorDocument` varchar(255) DEFAULT NULL,
  `redirectToMainDomain` tinyint(1) DEFAULT NULL,
  `creationDate` bigint(20) unsigned DEFAULT '0',
  `modificationDate` bigint(20) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rootId` (`rootId`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS  `tags`;
CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(10) unsigned DEFAULT NULL,
  `idPath` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idpath` (`idPath`),
  KEY `parentid` (`parentId`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS  `tags_assignment`;
CREATE TABLE `tags_assignment` (
  `tagid` int(10) unsigned NOT NULL DEFAULT '0',
  `cid` int(10) NOT NULL DEFAULT '0',
  `ctype` enum('document','asset','object') NOT NULL,
  PRIMARY KEY (`tagid`,`cid`,`ctype`),
  KEY `ctype` (`ctype`),
  KEY `ctype_cid` (`cid`,`ctype`),
  KEY `tagid` (`tagid`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `targeting_personas`;
CREATE TABLE `targeting_personas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `conditions` longtext,
  `threshold` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `targeting_rules`;
CREATE TABLE `targeting_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `scope` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `conditions` longtext,
  `actions` longtext,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tmp_store`;
CREATE TABLE `tmp_store` (
  `id` varchar(190) NOT NULL DEFAULT '',
  `tag` varchar(190) DEFAULT NULL,
  `data` longtext,
  `serialized` tinyint(2) NOT NULL DEFAULT '0',
  `date` int(10) DEFAULT NULL,
  `expiryDate` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tag` (`tag`),
  KEY `date` (`date`),
  KEY `expiryDate` (`expiryDate`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tracking_events`;
CREATE TABLE `tracking_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `data` varchar(255) DEFAULT NULL,
  `timestamp` bigint(20) unsigned DEFAULT NULL,
  `year` int(5) unsigned DEFAULT NULL,
  `month` int(2) unsigned DEFAULT NULL,
  `day` int(2) unsigned DEFAULT NULL,
  `dayOfWeek` int(1) unsigned DEFAULT NULL,
  `dayOfYear` int(3) unsigned DEFAULT NULL,
  `weekOfYear` int(2) unsigned DEFAULT NULL,
  `hour` int(2) unsigned DEFAULT NULL,
  `minute` int(2) unsigned DEFAULT NULL,
  `second` int(2) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timestamp` (`timestamp`),
  KEY `year` (`year`),
  KEY `month` (`month`),
  KEY `day` (`day`),
  KEY `dayOfWeek` (`dayOfWeek`),
  KEY `dayOfYear` (`dayOfYear`),
  KEY `weekOfYear` (`weekOfYear`),
  KEY `hour` (`hour`),
  KEY `minute` (`minute`),
  KEY `second` (`second`),
  KEY `category` (`category`),
  KEY `action` (`action`),
  KEY `label` (`label`),
  KEY `data` (`data`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `translations_admin`;
CREATE TABLE `translations_admin` (
  `key` varchar(190) NOT NULL DEFAULT '',
  `language` varchar(10) NOT NULL DEFAULT '',
  `text` text,
  `creationDate` bigint(20) unsigned DEFAULT NULL,
  `modificationDate` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`key`,`language`),
  KEY `language` (`language`),
  KEY `key` (`key`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `translations_website`;
CREATE TABLE `translations_website` (
  `key` varchar(190) NOT NULL DEFAULT '',
  `language` varchar(10) NOT NULL DEFAULT '',
  `text` text,
  `creationDate` bigint(20) unsigned DEFAULT NULL,
  `modificationDate` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`key`,`language`),
  KEY `language` (`language`),
  KEY `key` (`key`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tree_locks`;
CREATE TABLE `tree_locks` (
  `id` int(11) NOT NULL DEFAULT '0',
  `type` enum('asset','document','object') NOT NULL DEFAULT 'asset',
  `locked` enum('self','propagate') default NULL,
  PRIMARY KEY (`id`,`type`),
  KEY `id` (`id`),
  KEY `type` (`type`),
  KEY `locked` (`locked`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(11) unsigned DEFAULT NULL,
  `type` enum('user','userfolder','role','rolefolder') NOT NULL DEFAULT 'user',
  `name` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `language` varchar(10) DEFAULT NULL,
  `contentLanguages` LONGTEXT NULL,
  `admin` tinyint(1) unsigned DEFAULT '0',
  `active` tinyint(1) unsigned DEFAULT '1',
  `permissions` text,
  `roles` varchar(1000) DEFAULT NULL,
  `welcomescreen` tinyint(1) DEFAULT NULL,
  `closeWarning` tinyint(1) DEFAULT NULL,
  `memorizeTabs` tinyint(1) DEFAULT NULL,
  `allowDirtyClose` tinyint(1) unsigned DEFAULT '1',
  `docTypes` varchar(255) DEFAULT NULL,
  `classes` varchar(255) DEFAULT NULL,
  `apiKey` varchar(255) DEFAULT NULL,
	`activePerspective` VARCHAR(255) NULL DEFAULT NULL,
	`perspectives` LONGTEXT NULL DEFAULT NULL,
	`websiteTranslationLanguagesEdit` LONGTEXT NULL DEFAULT NULL,
  `websiteTranslationLanguagesView` LONGTEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_name` (`type`,`name`),
  KEY `parentId` (`parentId`),
  KEY `name` (`name`),
  KEY `password` (`password`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `users_permission_definitions`;
CREATE TABLE `users_permission_definitions` (
  `key` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`key`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `users_workspaces_asset`;
CREATE TABLE `users_workspaces_asset` (
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `cpath` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL, /* path in ascii using the full key length of 765 bytes (PIMCORE-2654) */
  `userId` int(11) NOT NULL DEFAULT '0',
  `list` tinyint(1) DEFAULT '0',
  `view` tinyint(1) DEFAULT '0',
  `publish` tinyint(1) DEFAULT '0',
  `delete` tinyint(1) DEFAULT '0',
  `rename` tinyint(1) DEFAULT '0',
  `create` tinyint(1) DEFAULT '0',
  `settings` tinyint(1) DEFAULT '0',
  `versions` tinyint(1) DEFAULT '0',
  `properties` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`cid`, `userId`),
  KEY `cid` (`cid`),
  KEY `userId` (`userId`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `users_workspaces_document`;
CREATE TABLE `users_workspaces_document` (
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `cpath` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL, /* path in ascii using the full key length of 765 bytes (PIMCORE-2654) */
  `userId` int(11) NOT NULL DEFAULT '0',
  `list` tinyint(1) unsigned DEFAULT '0',
  `view` tinyint(1) unsigned DEFAULT '0',
  `save` tinyint(1) unsigned DEFAULT '0',
  `publish` tinyint(1) unsigned DEFAULT '0',
  `unpublish` tinyint(1) unsigned DEFAULT '0',
  `delete` tinyint(1) unsigned DEFAULT '0',
  `rename` tinyint(1) unsigned DEFAULT '0',
  `create` tinyint(1) unsigned DEFAULT '0',
  `settings` tinyint(1) unsigned DEFAULT '0',
  `versions` tinyint(1) unsigned DEFAULT '0',
  `properties` tinyint(1) unsigned DEFAULT '0',
  PRIMARY KEY (`cid`, `userId`),
  KEY `cid` (`cid`),
  KEY `userId` (`userId`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `users_workspaces_object`;
CREATE TABLE `users_workspaces_object` (
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `cpath` varchar(765) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL, /* path in ascii using the full key length of 765 bytes (PIMCORE-2654) */
  `userId` int(11) NOT NULL DEFAULT '0',
  `list` tinyint(1) unsigned DEFAULT '0',
  `view` tinyint(1) unsigned DEFAULT '0',
  `save` tinyint(1) unsigned DEFAULT '0',
  `publish` tinyint(1) unsigned DEFAULT '0',
  `unpublish` tinyint(1) unsigned DEFAULT '0',
  `delete` tinyint(1) unsigned DEFAULT '0',
  `rename` tinyint(1) unsigned DEFAULT '0',
  `create` tinyint(1) unsigned DEFAULT '0',
  `settings` tinyint(1) unsigned DEFAULT '0',
  `versions` tinyint(1) unsigned DEFAULT '0',
  `properties` tinyint(1) unsigned DEFAULT '0',
  `lEdit` text,
  `lView` text,
  `layouts` text,
  PRIMARY KEY (`cid`, `userId`),
  KEY `cid` (`cid`),
  KEY `userId` (`userId`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `uuids`;
CREATE TABLE `uuids` (
  `uuid` CHAR(36) NOT NULL,
  `itemId` BIGINT(20) UNSIGNED NOT NULL,
  `type` VARCHAR(25) NOT NULL,
  `instanceIdentifier` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`itemId`, `type`, `uuid`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `versions`;
CREATE TABLE `versions` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `cid` int(11) unsigned default NULL,
  `ctype` enum('document','asset','object') default NULL,
  `userId` int(11) unsigned default NULL,
  `note` text,
  `stackTrace` text,
  `date` bigint(1) unsigned default NULL,
  `public` tinyint(1) unsigned NOT NULL default '0',
  `serialized` tinyint(1) unsigned default '0',
  PRIMARY KEY  (`id`),
  KEY `cid` (`cid`),
  KEY `ctype` (`ctype`),
  KEY `date` (`date`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `website_settings`;
CREATE TABLE `website_settings` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	`type` ENUM('text','document','asset','object','bool') DEFAULT NULL,
	`data` TEXT,
	`siteId` INT(11) UNSIGNED DEFAULT NULL,
	`creationDate` BIGINT(20) UNSIGNED DEFAULT '0',
	`modificationDate` BIGINT(20) UNSIGNED DEFAULT '0',
	PRIMARY KEY (`id`),
	INDEX `name` (`name`),
	INDEX `siteId` (`siteId`)
) DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `classificationstore_relations`;
DROP TABLE IF EXISTS `classificationstore_collectionrelations`;

DROP TABLE IF EXISTS `classificationstore_stores`;
CREATE TABLE `classificationstore_stores` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NULL DEFAULT NULL,
	`description` LONGTEXT NULL,
	PRIMARY KEY (`id`),
	INDEX `name` (`name`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `classificationstore_groups`;
CREATE TABLE `classificationstore_groups` (
	`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	`storeId` INT NULL DEFAULT NULL,
	`parentId` BIGINT(20) NOT NULL DEFAULT '0',
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	`description` VARCHAR(255) NULL DEFAULT NULL,
	`creationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	`modificationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	INDEX `storeId` (`storeId`),
	INDEX `name` (`name`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `classificationstore_keys`;
CREATE TABLE `classificationstore_keys` (
	`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	`storeId` INT NULL DEFAULT NULL,
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	`title` VARCHAR(255) NOT NULL DEFAULT '',
	`description` TEXT NULL,
	`type` VARCHAR(255) NULL DEFAULT NULL,
	`creationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	`modificationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	`definition` LONGTEXT NULL,
	`enabled` TINYINT(1) NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `name` (`name`),
	INDEX `enabled` (`enabled`),
	INDEX `type` (`type`),
	INDEX `storeId` (`storeId`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `classificationstore_relations` (
	`groupId` BIGINT(20) NOT NULL,
	`keyId` BIGINT(20) NOT NULL,
	`sorter` INT(11) NULL DEFAULT NULL,
	`mandatory` TINYINT(1) NULL DEFAULT NULL,
	PRIMARY KEY (`groupId`, `keyId`),
	INDEX `FK_classificationstore_relations_classificationstore_keys` (`keyId`),
	INDEX `groupId` (`groupId`),
	INDEX `mandatory` (`mandatory`),
	CONSTRAINT `FK_classificationstore_relations_classificationstore_groups` FOREIGN KEY (`groupId`) REFERENCES `classificationstore_groups` (`id`) ON DELETE CASCADE,
	CONSTRAINT `FK_classificationstore_relations_classificationstore_keys` FOREIGN KEY (`keyId`) REFERENCES `classificationstore_keys` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `classificationstore_collections`;
CREATE TABLE `classificationstore_collections` (
	`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	`storeId` INT NULL DEFAULT NULL,
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	`description` VARCHAR(255) NULL DEFAULT NULL,
	`creationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	`modificationDate` BIGINT(20) UNSIGNED NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	INDEX `storeId` (`storeId`)
) DEFAULT CHARSET=utf8mb4;


CREATE TABLE `classificationstore_collectionrelations` (
	`colId` BIGINT(20) NOT NULL,
	`groupId` BIGINT(20) NOT NULL,
    `sorter` INT(10) NULL DEFAULT '0',
	PRIMARY KEY (`colId`, `groupId`),
	INDEX `colId` (`colId`),
	CONSTRAINT `FK_classificationstore_collectionrelations_groups` FOREIGN KEY (`groupId`) REFERENCES `classificationstore_groups` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `quantityvalue_units`;
CREATE TABLE `quantityvalue_units` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `group` varchar(50) DEFAULT NULL,
  `abbreviation` varchar(10) NOT NULL,
  `longname` varchar(250) DEFAULT NULL,
  `baseunit` varchar(10) DEFAULT NULL,
  `factor` double DEFAULT NULL,
  `conversionOffset` DOUBLE NULL DEFAULT NULL,
  `reference` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `element_workflow_state`;
CREATE TABLE `element_workflow_state` (
  `cid` int(10) NOT NULL DEFAULT '0',
  `ctype` enum('document','asset','object') NOT NULL,
  `workflowId` int(11) NOT NULL,
  `state` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`cid`,`ctype`,`workflowId`)
) DEFAULT CHARSET=utf8mb4;
