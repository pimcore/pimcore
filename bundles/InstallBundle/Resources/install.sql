
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `application_logs`;
CREATE TABLE `application_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pid` INT(11) NULL DEFAULT NULL,
  `timestamp` datetime NOT NULL,
  `message` TEXT NULL,
  `priority` ENUM('emergency','alert','critical','error','warning','notice','info','debug') DEFAULT NULL,
  `fileobject` varchar(1024) DEFAULT NULL,
  `info` varchar(1024) DEFAULT NULL,
  `component` varchar(190) DEFAULT NULL,
  `source` varchar(190) DEFAULT NULL,
  `relatedobject` int(11) unsigned DEFAULT NULL,
  `relatedobjecttype` enum('object','document','asset') DEFAULT NULL,
  `maintenanceChecked` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `component` (`component`),
  KEY `timestamp` (`timestamp`),
  KEY `relatedobject` (`relatedobject`),
  KEY `priority` (`priority`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `assets`;
CREATE TABLE `assets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(11) unsigned DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `path` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL, /* path in utf8 (3-byte) using the full key length of 3072 bytes */
  `mimetype` varchar(190) DEFAULT NULL,
  `creationDate` INT(11) UNSIGNED DEFAULT NULL,
  `modificationDate` INT(11) UNSIGNED DEFAULT NULL,
  `userOwner` int(11) unsigned DEFAULT NULL,
  `userModification` int(11) unsigned DEFAULT NULL,
  `customSettings` longtext,
  `hasMetaData` tinyint(1) NOT NULL DEFAULT '0',
  `versionCount` INT UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fullpath` (`path`,`filename`),
  KEY `parentId` (`parentId`),
  KEY `filename` (`filename`),
  KEY `modificationDate` (`modificationDate`)
) AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `assets_metadata`;
CREATE TABLE `assets_metadata` (
  `cid` int(11) NOT NULL,
  `name` varchar(190) NOT NULL,
  `language` varchar(10) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  `type` ENUM('input','textarea','asset','document','object','date','select','checkbox') DEFAULT NULL,
  `data` longtext,
  PRIMARY KEY (`cid`, `name`, `language`),
	INDEX `name` (`name`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `id` varchar(165) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  `data` longblob,
  `mtime` INT(11) UNSIGNED DEFAULT NULL,
  `expire` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `cache_tags`;
CREATE TABLE `cache_tags` (
  `id` varchar(165) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  `tag` varchar(165) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`,`tag`),
  INDEX `tag` (`tag`)
) DEFAULT CHARSET=ascii;

DROP TABLE IF EXISTS `classes` ;
CREATE TABLE `classes` (
	`id` VARCHAR(50) NOT NULL,
	`name` VARCHAR(190) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	UNIQUE INDEX `name` (`name`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `custom_layouts` ;
CREATE TABLE `custom_layouts` (
	`id` varchar(64) NOT NULL,
	`classId` VARCHAR(50) NOT NULL,
	`name` VARCHAR(190) NULL DEFAULT NULL,
	`description` TEXT NULL,
	`creationDate` INT(11) UNSIGNED NULL DEFAULT NULL,
	`modificationDate` INT(11) UNSIGNED NULL DEFAULT NULL,
	`userOwner` INT(11) UNSIGNED NULL DEFAULT NULL,
	`userModification` INT(11) UNSIGNED NULL DEFAULT NULL,
	`default` tinyint(1) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE INDEX `name` (`name`, `classId`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `dependencies` ;
CREATE TABLE `dependencies` (
	`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	`sourcetype` ENUM('document','asset','object') NOT NULL DEFAULT 'document',
	`sourceid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
	`targettype` ENUM('document','asset','object') NOT NULL DEFAULT 'document',
	`targetid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE INDEX `combi` (`sourcetype`, `sourceid`, `targettype`, `targetid`),
	INDEX `targettype_targetid` (`targettype`, `targetid`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents` ;
CREATE TABLE `documents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(11) unsigned DEFAULT NULL,
  `type` enum('page','link','snippet','folder','hardlink','email','newsletter','printpage','printcontainer') DEFAULT NULL,
  `key` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `path` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL, /* path in utf8 (3-byte) using the full key length of 3072 bytes */
  `index` int(11) unsigned DEFAULT '0',
  `published` tinyint(1) unsigned DEFAULT '1',
  `creationDate` INT(11) UNSIGNED DEFAULT NULL,
  `modificationDate` INT(11) UNSIGNED DEFAULT NULL,
  `userOwner` int(11) unsigned DEFAULT NULL,
  `userModification` int(11) unsigned DEFAULT NULL,
  `versionCount` INT UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fullpath` (`path`,`key`),
  KEY `parentId` (`parentId`),
  KEY `key` (`key`),
  KEY `published` (`published`),
  KEY `modificationDate` (`modificationDate`)
) AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `documents_elements`;
CREATE TABLE `documents_elements` (
  `documentId` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(750) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  `type` varchar(50) DEFAULT NULL,
  `data` longtext,
  PRIMARY KEY (`documentId`,`name`)
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
  `replyTo` varchar(255) DEFAULT NULL,
  `cc` varchar(255) DEFAULT NULL,
  `bcc` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL,
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
  `plaintext` LONGTEXT NULL DEFAULT NULL,
  `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_hardlink`;
CREATE TABLE `documents_hardlink` (
  `id` int(11) unsigned NOT NULL default '0',
  `sourceId` int(11) DEFAULT NULL,
  `propertiesFromSource` tinyint(1) DEFAULT NULL,
  `childrenFromSource` tinyint(1) DEFAULT NULL,
  PRIMARY KEY `id` (`id`),
  KEY `sourceId` (`sourceId`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_link`;
CREATE TABLE `documents_link` (
  `id` int(11) unsigned NOT NULL default '0',
  `internalType` enum('document','asset','object') default NULL,
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
  `description` varchar(383) DEFAULT NULL,
  `metaData` text,
  `prettyUrl` varchar(190) DEFAULT NULL,
  `contentMasterDocumentId` int(11) DEFAULT NULL,
  `targetGroupIds` varchar(255) DEFAULT NULL,
  `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL,
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
  `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_translations`;
CREATE TABLE `documents_translations` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `sourceId` int(11) unsigned NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`sourceId`,`language`),
  KEY `id` (`id`),
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
  `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL,
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
  `replyTo` varchar(255) DEFAULT NULL,
  `to` longtext,
  `cc` longtext,
  `bcc` longtext,
  `sentDate` int(11) UNSIGNED DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sentDate` (`sentDate`, `id`),
  FULLTEXT KEY `fulltext` (`from`,`to`,`cc`,`bcc`,`subject`,`params`)
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
  `creationDate` int(11) unsigned DEFAULT '0',
  `modificationDate` int(11) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `language` (`language`),
  KEY `site` (`site`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `http_error_log`;
CREATE TABLE `http_error_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uri` varchar(1024) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `code` int(3) DEFAULT NULL,
  `parametersGet` longtext,
  `parametersPost` longtext,
  `cookies` longtext,
  `serverVars` longtext,
  `date` int(11) unsigned DEFAULT NULL,
  `count` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uri` (`uri`),
  KEY `code` (`code`),
  KEY `date` (`date`),
  KEY `count` (`count`)
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `locks`;
CREATE TABLE `locks` (
  `id` varchar(150) NOT NULL DEFAULT '',
  `date` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `lock_keys`;
CREATE TABLE `lock_keys` (
  `key_id` varchar(64) NOT NULL,
  `key_token` varchar(44) NOT NULL,
  `key_expiration` int(10) unsigned NOT NULL,
  PRIMARY KEY (`key_id`)
) DEFAULT CHARSET=utf8;

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
  KEY `cid_ctype` (`cid`, `ctype`),
  KEY `date` (`date`),
  KEY `user` (`user`)
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
  `o_key` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin default '',
  `o_path` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL, /* path in utf8 (3-byte) using the full key length of 3072 bytes */
  `o_index` int(11) unsigned DEFAULT '0',
  `o_published` tinyint(1) unsigned DEFAULT '1',
  `o_creationDate` int(11) unsigned DEFAULT NULL,
  `o_modificationDate` int(11) unsigned DEFAULT NULL,
  `o_userOwner` int(11) unsigned DEFAULT NULL,
  `o_userModification` int(11) unsigned DEFAULT NULL,
  `o_classId` VARCHAR(50) NULL DEFAULT NULL,
  `o_className` varchar(255) DEFAULT NULL,
  `o_childrenSortBy` ENUM('key','index') NULL DEFAULT NULL,
  `o_childrenSortOrder` ENUM('ASC','DESC') NULL DEFAULT NULL,
  `o_versionCount` INT UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`o_id`),
  UNIQUE KEY `fullpath` (`o_path`,`o_key`),
  KEY `key` (`o_key`),
  KEY `index` (`o_index`),
  KEY `published` (`o_published`),
  KEY `parentId` (`o_parentId`),
  KEY `type` (`o_type`),
  KEY `o_modificationDate` (`o_modificationDate`),
  KEY `o_classId` (`o_classId`)
) AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `properties`;
CREATE TABLE `properties` (
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `ctype` enum('document','asset','object') NOT NULL DEFAULT 'document',
  `cpath` varchar(765) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL, /* path in utf8 (3-byte) using the full key length of 3072 bytes */
  `name` varchar(190) NOT NULL DEFAULT '',
  `type` enum('text','document','asset','object','bool','select') DEFAULT NULL,
  `data` text,
  `inheritable` tinyint(1) unsigned DEFAULT '1',
  PRIMARY KEY (`cid`,`ctype`,`name`),
  KEY `getall` (`cpath`, `ctype`, `inheritable`)
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `recyclebin`;
CREATE TABLE `recyclebin` (
  `id` int(11) NOT NULL auto_increment,
  `type` varchar(20) default NULL,
  `subtype` varchar(20) default NULL,
  `path` varchar(765) default NULL,
  `amount` int(3) default NULL,
  `date` int(11) unsigned default NULL,
  `deletedby` varchar(50) DEFAULT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `redirects`;
CREATE TABLE `redirects` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` ENUM('entire_uri','path_query','path','auto_create') NOT NULL,
  `source` varchar(255) DEFAULT NULL,
  `sourceSite` int(11) DEFAULT NULL,
  `target` varchar(255) DEFAULT NULL,
  `targetSite` int(11) DEFAULT NULL,
  `statusCode` varchar(3) DEFAULT NULL,
  `priority` int(2) DEFAULT '0',
  `regex` tinyint(1) DEFAULT NULL,
  `passThroughParameters` tinyint(1) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `expiry` int(11) unsigned DEFAULT NULL,
  `creationDate` int(11) unsigned DEFAULT '0',
  `modificationDate` int(11) unsigned DEFAULT '0',
  `userOwner` int(11) unsigned DEFAULT NULL,
  `userModification` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `priority` (`priority`),
  INDEX `routing_lookup` (`active`, `regex`, `sourceSite`, `source`, `type`, `expiry`, `priority`)
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

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
  `date` int(11) unsigned DEFAULT NULL,
  `action` enum('publish','unpublish','delete','publish-version') DEFAULT NULL,
  `version` bigint(20) unsigned DEFAULT NULL,
  `active` tinyint(1) unsigned DEFAULT '0',
  `userId` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `ctype` (`ctype`),
  KEY `active` (`active`),
  KEY `version` (`version`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `search_backend_data`;
CREATE TABLE `search_backend_data` (
  `id` int(11) NOT NULL,
  `fullpath` varchar(765) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL, /* path in utf8 (3-byte) using the full key length of 3072 bytes */
  `maintype` varchar(8) NOT NULL DEFAULT '',
  `type` varchar(20) DEFAULT NULL,
  `subtype` varchar(190) DEFAULT NULL,
  `published` tinyint(1) unsigned DEFAULT NULL,
  `creationDate` int(11) unsigned DEFAULT NULL,
  `modificationDate` int(11) unsigned DEFAULT NULL,
  `userOwner` int(11) DEFAULT NULL,
  `userModification` int(11) DEFAULT NULL,
  `data` longtext,
  `properties` text,
  PRIMARY KEY (`id`,`maintype`),
  KEY `fullpath` (`fullpath`),
  KEY `maintype` (`maintype`),
  KEY `type` (`type`),
  KEY `subtype` (`subtype`),
  KEY `published` (`published`),
  FULLTEXT KEY `fulltext` (`data`,`properties`)
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `sites`;
CREATE TABLE `sites` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mainDomain` varchar(255) DEFAULT NULL,
  `domains` text,
  `rootId` int(11) unsigned DEFAULT NULL,
  `errorDocument` varchar(255) DEFAULT NULL,
  `redirectToMainDomain` tinyint(1) DEFAULT NULL,
  `creationDate` int(11) unsigned DEFAULT '0',
  `modificationDate` int(11) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rootId` (`rootId`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS  `tags`;
CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(10) unsigned DEFAULT NULL,
  `idPath` varchar(190) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL COLLATE utf8_bin,
  PRIMARY KEY (`id`),
  KEY `idpath` (`idPath`),
  KEY `parentid` (`parentId`),
  KEY `name` (`name`),
  UNIQUE INDEX `idPath_name` (`idPath`,`name`)
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS  `tags_assignment`;
CREATE TABLE `tags_assignment` (
  `tagid` int(10) unsigned NOT NULL DEFAULT '0',
  `cid` int(10) NOT NULL DEFAULT '0',
  `ctype` enum('document','asset','object') NOT NULL,
  PRIMARY KEY (`tagid`,`cid`,`ctype`),
  KEY `ctype` (`ctype`),
  KEY `ctype_cid` (`cid`,`ctype`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `targeting_rules`;
CREATE TABLE `targeting_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `scope` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `prio` smallint(5) unsigned NOT NULL DEFAULT '0',
  `conditions` longtext,
  `actions` longtext,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `targeting_storage`;
CREATE TABLE `targeting_storage` (
  `visitorId` varchar(100) NOT NULL,
  `scope` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` text,
  `creationDate` datetime DEFAULT NULL,
  `modificationDate` datetime DEFAULT NULL,
  PRIMARY KEY (`visitorId`,`scope`,`name`),
  KEY `targeting_storage_scope_index` (`scope`),
  KEY `targeting_storage_name_index` (`name`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `targeting_target_groups`;
CREATE TABLE `targeting_target_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `threshold` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tmp_store`;
CREATE TABLE `tmp_store` (
  `id` varchar(190) NOT NULL DEFAULT '',
  `tag` varchar(190) DEFAULT NULL,
  `data` longtext,
  `serialized` tinyint(2) NOT NULL DEFAULT '0',
  `date` int(11) unsigned DEFAULT NULL,
  `expiryDate` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tag` (`tag`),
  KEY `date` (`date`),
  KEY `expiryDate` (`expiryDate`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tracking_events`;
CREATE TABLE `tracking_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(190) DEFAULT NULL,
  `action` varchar(190) DEFAULT NULL,
  `label` varchar(190) DEFAULT NULL,
  `data` varchar(190) DEFAULT NULL,
  `timestamp` int(11) unsigned DEFAULT NULL,
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
  `key` varchar(190) NOT NULL DEFAULT '' COLLATE 'utf8mb4_bin',
  `language` varchar(10) NOT NULL DEFAULT '',
  `text` text,
  `creationDate` int(11) unsigned DEFAULT NULL,
  `modificationDate` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`key`,`language`),
  KEY `language` (`language`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `translations_website`;
CREATE TABLE `translations_website` (
  `key` varchar(190) NOT NULL DEFAULT '' COLLATE 'utf8mb4_bin',
  `language` varchar(10) NOT NULL DEFAULT '',
  `text` text,
  `creationDate` int(11) unsigned DEFAULT NULL,
  `modificationDate` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`key`,`language`),
  KEY `language` (`language`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tree_locks`;
CREATE TABLE `tree_locks` (
  `id` int(11) NOT NULL DEFAULT '0',
  `type` enum('asset','document','object') NOT NULL DEFAULT 'asset',
  `locked` enum('self','propagate') default NULL,
  PRIMARY KEY (`id`,`type`),
  KEY `type` (`type`),
  KEY `locked` (`locked`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(11) unsigned DEFAULT NULL,
  `type` enum('user','userfolder','role','rolefolder') NOT NULL DEFAULT 'user',
  `name` varchar(50) DEFAULT NULL,
  `password` varchar(190) DEFAULT NULL,
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
  `classes` text DEFAULT NULL,
  `apiKey` varchar(255) DEFAULT NULL,
  `twoFactorAuthentication` varchar(255) DEFAULT NULL,
	`activePerspective` VARCHAR(255) NULL DEFAULT NULL,
	`perspectives` LONGTEXT NULL DEFAULT NULL,
	`websiteTranslationLanguagesEdit` LONGTEXT NULL DEFAULT NULL,
  `websiteTranslationLanguagesView` LONGTEXT NULL DEFAULT NULL,
  `lastLogin` int(11) unsigned DEFAULT NULL,
  `keyBindings` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_name` (`type`,`name`),
  KEY `parentId` (`parentId`),
  KEY `name` (`name`),
  KEY `password` (`password`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `users_permission_definitions`;
CREATE TABLE `users_permission_definitions` (
  `key` varchar(50) NOT NULL DEFAULT '',
  `category` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`key`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `users_workspaces_asset`;
CREATE TABLE `users_workspaces_asset` (
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `cpath` varchar(765) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL, /* path in utf8 (3-byte) using the full key length of 3072 bytes */
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
  KEY `userId` (`userId`),
  UNIQUE INDEX `cpath_userId` (`cpath`,`userId`)
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `users_workspaces_document`;
CREATE TABLE `users_workspaces_document` (
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `cpath` varchar(765) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL, /* path in utf8 (3-byte) using the full key length of 3072 bytes */
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
  KEY `userId` (`userId`),
  UNIQUE INDEX `cpath_userId` (`cpath`,`userId`)
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `users_workspaces_object`;
CREATE TABLE `users_workspaces_object` (
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `cpath` varchar(765) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL, /* path in utf8 (3-byte) using the full key length of 3072 bytes */
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
  KEY `userId` (`userId`),
  UNIQUE INDEX `cpath_userId` (`cpath`,`userId`)
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `uuids`;
CREATE TABLE `uuids` (
  `uuid` CHAR(36) NOT NULL,
  `itemId` int(11) unsigned NOT NULL,
  `type` VARCHAR(25) NOT NULL,
  `instanceIdentifier` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`uuid`, `itemId`, `type`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `versions`;
CREATE TABLE `versions` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `cid` int(11) unsigned default NULL,
  `ctype` enum('document','asset','object') default NULL,
  `userId` int(11) unsigned default NULL,
  `note` text,
  `stackTrace` text,
  `date` int(11) unsigned default NULL,
  `public` tinyint(1) unsigned NOT NULL default '0',
  `serialized` tinyint(1) unsigned default '0',
  `versionCount` INT UNSIGNED NOT NULL DEFAULT '0',
  `binaryFileHash` VARCHAR(128) NULL DEFAULT NULL COLLATE 'ascii_general_ci',
  `binaryFileId` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `cid` (`cid`),
  KEY `ctype_cid` (`ctype`, `cid`),
  KEY `date` (`date`),
  KEY `binaryFileHash` (`binaryFileHash`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `classificationstore_relations`;
DROP TABLE IF EXISTS `classificationstore_collectionrelations`;

DROP TABLE IF EXISTS `classificationstore_stores`;
CREATE TABLE `classificationstore_stores` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(190) NULL DEFAULT NULL,
	`description` LONGTEXT NULL,
	PRIMARY KEY (`id`),
	INDEX `name` (`name`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `classificationstore_groups`;
CREATE TABLE `classificationstore_groups` (
	`id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
	`storeId` INT NULL DEFAULT NULL,
	`parentId` INT(11) unsigned NOT NULL DEFAULT '0',
	`name` VARCHAR(190) NOT NULL DEFAULT '',
	`description` VARCHAR(255) NULL DEFAULT NULL,
	`creationDate` INT(11) UNSIGNED NULL DEFAULT '0',
	`modificationDate` INT(11) UNSIGNED NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	INDEX `storeId` (`storeId`),
	INDEX `name` (`name`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `classificationstore_keys`;
CREATE TABLE `classificationstore_keys` (
	`id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
	`storeId` INT NULL DEFAULT NULL,
	`name` VARCHAR(190) NOT NULL DEFAULT '',
	`title` VARCHAR(255) NOT NULL DEFAULT '',
	`description` TEXT NULL,
	`type` VARCHAR(190) NULL DEFAULT NULL,
	`creationDate` INT(11) UNSIGNED NULL DEFAULT '0',
	`modificationDate` INT(11) UNSIGNED NULL DEFAULT '0',
	`definition` LONGTEXT NULL,
	`enabled` TINYINT(1) NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `name` (`name`),
	INDEX `enabled` (`enabled`),
	INDEX `type` (`type`),
	INDEX `storeId` (`storeId`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE `classificationstore_relations` (
	`groupId` INT(11) unsigned NOT NULL,
	`keyId` INT(11) unsigned NOT NULL,
	`sorter` INT(11) NULL DEFAULT NULL,
	`mandatory` TINYINT(1) NULL DEFAULT NULL,
	PRIMARY KEY (`groupId`, `keyId`),
	INDEX `FK_classificationstore_relations_classificationstore_keys` (`keyId`),
	INDEX `mandatory` (`mandatory`),
	CONSTRAINT `FK_classificationstore_relations_classificationstore_groups` FOREIGN KEY (`groupId`) REFERENCES `classificationstore_groups` (`id`) ON DELETE CASCADE,
	CONSTRAINT `FK_classificationstore_relations_classificationstore_keys` FOREIGN KEY (`keyId`) REFERENCES `classificationstore_keys` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `classificationstore_collections`;
CREATE TABLE `classificationstore_collections` (
	`id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
	`storeId` INT NULL DEFAULT NULL,
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	`description` VARCHAR(255) NULL DEFAULT NULL,
	`creationDate` INT(11) UNSIGNED NULL DEFAULT '0',
	`modificationDate` INT(11) UNSIGNED NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	INDEX `storeId` (`storeId`)
) DEFAULT CHARSET=utf8mb4;


CREATE TABLE `classificationstore_collectionrelations` (
	`colId` INT(11) unsigned NOT NULL,
	`groupId` INT(11) unsigned NOT NULL,
    `sorter` INT(10) NULL DEFAULT '0',
	PRIMARY KEY (`colId`, `groupId`),
	CONSTRAINT `FK_classificationstore_collectionrelations_groups` FOREIGN KEY (`groupId`) REFERENCES `classificationstore_groups` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `quantityvalue_units`;
CREATE TABLE `quantityvalue_units` (
	`id` VARCHAR(50) NOT NULL,
	`group` VARCHAR(50) NULL DEFAULT NULL,
	`abbreviation` VARCHAR(20) NULL DEFAULT NULL,
	`longname` VARCHAR(250) NULL DEFAULT NULL,
	`baseunit` VARCHAR(50) NULL DEFAULT NULL,
	`factor` DOUBLE NULL DEFAULT NULL,
	`conversionOffset` DOUBLE NULL DEFAULT NULL,
	`reference` VARCHAR(50) NULL DEFAULT NULL,
	`converter` VARCHAR(255) NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `fk_baseunit` (`baseunit`),
	CONSTRAINT `fk_baseunit` FOREIGN KEY (`baseunit`) REFERENCES `quantityvalue_units` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `element_workflow_state`;
CREATE TABLE `element_workflow_state` (
  `cid` int(10) NOT NULL DEFAULT '0',
  `ctype` enum('document','asset','object') NOT NULL,
  `place` text DEFAULT NULL,
  `workflow` varchar(100) NOT NULL,
  PRIMARY KEY (`cid`,`ctype`,`workflow`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `gridconfigs`;
CREATE TABLE `gridconfigs` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`ownerId` INT(11) NULL,
	`classId` VARCHAR(50) NULL DEFAULT NULL,
	`name` VARCHAR(50) NULL,
	`searchType` VARCHAR(50) NULL,
	`type` enum('asset','object') NOT NULL DEFAULT 'object',
	`config` LONGTEXT NULL,
	`description` LONGTEXT NULL,
	`creationDate` INT(11) NULL,
	`modificationDate` INT(11) NULL,
	`shareGlobally` TINYINT(1) NULL,
	PRIMARY KEY (`id`),
	INDEX `ownerId` (`ownerId`),
	INDEX `classId` (`classId`),
	INDEX `searchType` (`searchType`),
	INDEX `shareGlobally` (`shareGlobally`)
)
DEFAULT CHARSET=utf8mb4;
;

DROP TABLE IF EXISTS `gridconfig_favourites`;
CREATE TABLE `gridconfig_favourites` (
	`ownerId` INT(11) NOT NULL,
	`classId` VARCHAR(50) NOT NULL,
    `objectId` INT(11) NOT NULL DEFAULT '0',
	`gridConfigId` INT(11) NULL,
	`searchType` VARCHAR(50) NOT NULL DEFAULT '',
	`type` enum('asset','object') NOT NULL DEFAULT 'object',
  PRIMARY KEY (`ownerId`, `classId`, `searchType`, `objectId`),
	INDEX `classId` (`classId`),
	INDEX `searchType` (`searchType`)
)
DEFAULT CHARSET=utf8mb4;
;

DROP TABLE IF EXISTS `gridconfig_shares`;
CREATE TABLE `gridconfig_shares` (
	`gridConfigId` INT(11) NOT NULL,
	`sharedWithUserId` INT(11) NOT NULL,
	PRIMARY KEY (`gridConfigId`, `sharedWithUserId`),
	INDEX `sharedWithUserId` (`sharedWithUserId`)
)
DEFAULT CHARSET=utf8mb4;
;

DROP TABLE IF EXISTS `importconfigs`;
CREATE TABLE `importconfigs` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`ownerId` INT(11) NULL,
	`classId` VARCHAR(50) NULL DEFAULT NULL,
	`name` VARCHAR(50) NULL,
	`config` LONGTEXT NULL,
  `description` LONGTEXT NULL,
	`creationDate` INT(11) NULL,
	`modificationDate` INT(11) NULL,
	`shareGlobally` TINYINT(1) NULL,
	PRIMARY KEY (`id`),
	INDEX `ownerId` (`ownerId`),
	INDEX `classId` (`classId`),
	INDEX `shareGlobally` (`shareGlobally`)
)
DEFAULT CHARSET=utf8mb4;
;

DROP TABLE IF EXISTS `importconfig_shares`;
CREATE TABLE `importconfig_shares` (
	`importConfigId` INT(11) NOT NULL,
	`sharedWithUserId` INT(11) NOT NULL,
	PRIMARY KEY (`importConfigId`, `sharedWithUserId`),
	INDEX `sharedWithUserId` (`sharedWithUserId`)
)
DEFAULT CHARSET=utf8mb4;
;

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT(11)  AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(20) DEFAULT 'info' NOT NULL,
  `title` VARCHAR(250) DEFAULT '' NOT NULL,
  `message` TEXT NOT NULL,
  `sender` INT(11) NULL,
  `recipient` INT(11) NOT NULL,
  `read` TINYINT(1) default '0' NOT NULL,
  `creationDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `modificationDate` TIMESTAMP NULL,
  `linkedElementType` ENUM('document', 'asset', 'object') NULL,
  `linkedElement` INT(11) NULL,
  INDEX `recipient` (`recipient`)
)
DEFAULT CHARSET=utf8mb4;
;

DROP TABLE IF EXISTS `object_url_slugs`;
CREATE TABLE `object_url_slugs` (
      `objectId` INT(11) NOT NULL DEFAULT '0',
	    `classId` VARCHAR(50) NOT NULL DEFAULT '0',
      `fieldname` VARCHAR(70) NOT NULL DEFAULT '0',
      `index` INT(11) UNSIGNED NOT NULL DEFAULT '0',
      `ownertype` ENUM('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
      `ownername` VARCHAR(70) NOT NULL DEFAULT '',
      `position` VARCHAR(70) NOT NULL DEFAULT '0',
      `slug` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, /* slug in utf8 (3-byte) using the full key length of 3072 bytes */
      `siteId` INT(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`slug`, `siteId`),
      INDEX `index` (`index`),
      INDEX `objectId` (`objectId`),
      INDEX `classId` (`classId`),
      INDEX `fieldname` (`fieldname`),
      INDEX `position` (`position`),
      INDEX `ownertype` (`ownertype`),
      INDEX `ownername` (`ownername`),
      INDEX `slug` (`slug`),
      INDEX `siteId` (`siteId`)
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
