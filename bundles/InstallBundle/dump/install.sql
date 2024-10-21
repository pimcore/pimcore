SET NAMES utf8mb4;

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
  `dataModificationDate` INT(11) UNSIGNED DEFAULT NULL,
  `userOwner` int(11) unsigned DEFAULT NULL,
  `userModification` int(11) unsigned DEFAULT NULL,
  `customSettings` longtext,
  `hasMetaData` tinyint(1) NOT NULL DEFAULT '0',
  `versionCount` INT UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fullpath` (`path`,`filename`),
  KEY `parentId` (`parentId`),
  KEY `filename` (`filename`),
  KEY `modificationDate` (`modificationDate`),
  KEY `versionCount` (`versionCount`)
) AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `assets_metadata`;
CREATE TABLE `assets_metadata` (
  `cid` int(11) unsigned NOT NULL,
  `name` varchar(190) NOT NULL,
  `language` varchar(10) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  `type` ENUM('input','textarea','asset','document','object','date','select','checkbox') DEFAULT NULL,
  `data` longtext,
  PRIMARY KEY (`cid`, `name`, `language`),
  INDEX `name` (`name`),
  CONSTRAINT `FK_assets_metadata_assets` FOREIGN KEY (`cid`) REFERENCES `assets` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `assets_image_thumbnail_cache`;
CREATE TABLE `assets_image_thumbnail_cache` (
    `cid` int(11) unsigned NOT NULL,
    `name` varchar(190) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `filename` varchar(190) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
    `modificationDate` INT(11) UNSIGNED DEFAULT NULL,
    `filesize` INT(11) UNSIGNED DEFAULT NULL,
    `width` SMALLINT UNSIGNED DEFAULT NULL,
    `height` SMALLINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`cid`, `name`, `filename`),
    CONSTRAINT `FK_assets_image_thumbnail_cache_assets` FOREIGN KEY (`cid`) REFERENCES `assets` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `cache_items`; /* this table is created by the installer (see: Pimcore\Bundle\InstallBundle\Installer::setupDatabase) */

DROP TABLE IF EXISTS `classes` ;
CREATE TABLE `classes` (
	`id` VARCHAR(50) NOT NULL,
	`name` VARCHAR(190) NOT NULL DEFAULT '',
    `definitionModificationDate` INT(11) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `name` (`name`)
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
  `type` enum('page','link','snippet','folder','hardlink','email') DEFAULT NULL,
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
  KEY `modificationDate` (`modificationDate`),
  KEY `versionCount` (`versionCount`)
) AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `documents_editables`;
CREATE TABLE `documents_editables` (
  `documentId` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(750) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
  `type` varchar(50) DEFAULT NULL,
  `data` longtext,
  PRIMARY KEY (`documentId`,`name`),
  CONSTRAINT `fk_documents_editables_documents` FOREIGN KEY (`documentId`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_email`;
CREATE TABLE `documents_email` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `controller` varchar(500) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `to` varchar(255) DEFAULT NULL,
  `from` varchar(255) DEFAULT NULL,
  `replyTo` varchar(255) DEFAULT NULL,
  `cc` varchar(255) DEFAULT NULL,
  `bcc` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_documents_email_documents` FOREIGN KEY (`id`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_hardlink`;
CREATE TABLE `documents_hardlink` (
  `id` int(11) unsigned NOT NULL default '0',
  `sourceId` int(11) DEFAULT NULL,
  `propertiesFromSource` tinyint(1) DEFAULT NULL,
  `childrenFromSource` tinyint(1) DEFAULT NULL,
  PRIMARY KEY `id` (`id`),
  KEY `sourceId` (`sourceId`),
  CONSTRAINT `fk_documents_hardlink_documents` FOREIGN KEY (`id`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_link`;
CREATE TABLE `documents_link` (
  `id` int(11) unsigned NOT NULL default '0',
  `internalType` enum('document','asset','object') default NULL,
  `internal` int(11) unsigned default NULL,
  `direct` varchar(1000) default NULL,
  `linktype` enum('direct','internal') default NULL,
  PRIMARY KEY  (`id`),
  CONSTRAINT `fk_documents_link_documents` FOREIGN KEY (`id`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_page` ;
CREATE TABLE `documents_page` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `controller` varchar(500) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(383) DEFAULT NULL,
  `prettyUrl` varchar(255) DEFAULT NULL,
  `contentMainDocumentId` int(11) DEFAULT NULL,
  `targetGroupIds` varchar(255) NOT NULL DEFAULT '',
  `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL,
  `staticGeneratorEnabled` tinyint(1) unsigned DEFAULT NULL,
  `staticGeneratorLifetime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prettyUrl` (`prettyUrl`),
  CONSTRAINT `fk_documents_page_documents` FOREIGN KEY (`id`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_snippet`;
CREATE TABLE `documents_snippet` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `controller` varchar(500) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `contentMainDocumentId` int(11) DEFAULT NULL,
  `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_documents_snippet_documents` FOREIGN KEY (`id`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents_translations`;
CREATE TABLE `documents_translations` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `sourceId` int(11) unsigned NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`sourceId`,`language`),
  KEY `id` (`id`),
  KEY `language` (`language`),
  CONSTRAINT `fk_documents_translations_documents` FOREIGN KEY (`id`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
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


DROP TABLE IF EXISTS `email_blocklist`;
CREATE TABLE `email_blocklist` (
  `address` varchar(190) NOT NULL DEFAULT '',
  `creationDate` int(11) unsigned DEFAULT NULL,
  `modificationDate` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`address`)
) DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `email_log`;
CREATE TABLE `email_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `documentId` int(11) unsigned DEFAULT NULL,
  `requestUri` varchar(500) DEFAULT NULL,
  `params` text,
  `from` varchar(500) DEFAULT NULL,
  `replyTo` varchar(255) DEFAULT NULL,
  `to` longtext,
  `cc` longtext,
  `bcc` longtext,
  `sentDate` int(11) UNSIGNED DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `error` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sentDate` (`sentDate`, `id`),
  FULLTEXT KEY `fulltext` (`from`,`to`,`cc`,`bcc`,`subject`,`params`),
  INDEX `document_id` (`documentId`),
  CONSTRAINT `fk_email_log_documents` FOREIGN KEY (`documentId`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `lock_keys`;
CREATE TABLE `lock_keys` (
  `key_id` varchar(64) NOT NULL,
  `key_token` varchar(44) NOT NULL,
  `key_expiration` int(10) unsigned NOT NULL,
  PRIMARY KEY (`key_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `migration_versions`; /* table is created using doctrine:migrations:sync-metadata-storage command */

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
  `locked` tinyint(1) unsigned DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `cid_ctype` (`cid`, `ctype`),
  KEY `date` (`date`),
  KEY `user` (`user`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `notes_data`;
CREATE TABLE `notes_data` (
  `auto_id` int(11) NOT NULL AUTO_INCREMENT,
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('text','date','document','asset','object','bool') DEFAULT NULL,
  `data` text,
  PRIMARY KEY (`auto_id`),
  UNIQUE KEY `UNIQ_E5A8E5E2BF3967505E237E06` (`id`,`name`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `objects`;
CREATE TABLE `objects` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(11) unsigned DEFAULT NULL,
  `type` enum('object','folder','variant') DEFAULT NULL,
  `key` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin default '',
  `path` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL, /* path in utf8 (3-byte) using the full key length of 3072 bytes */
  `index` int(11) unsigned DEFAULT '0',
  `published` tinyint(1) unsigned DEFAULT '1',
  `creationDate` int(11) unsigned DEFAULT NULL,
  `modificationDate` int(11) unsigned DEFAULT NULL,
  `userOwner` int(11) unsigned DEFAULT NULL,
  `userModification` int(11) unsigned DEFAULT NULL,
  `classId` VARCHAR(50) NULL DEFAULT NULL,
  `className` varchar(255) DEFAULT NULL,
  `childrenSortBy` ENUM('key','index') NULL DEFAULT NULL,
  `childrenSortOrder` ENUM('ASC','DESC') NULL DEFAULT NULL,
  `versionCount` INT UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fullpath` (`path`,`key`),
  KEY `key` (`key`),
  KEY `index` (`index`),
  KEY `published` (`published`),
  KEY `parentId` (`parentId`),
  KEY `type_path_classId` (`type`, `path`, `classId`),
  KEY `modificationDate` (`modificationDate`),
  KEY `classId` (`classId`),
  KEY `versionCount` (`versionCount`)
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
  PRIMARY KEY  (`id`),
  INDEX `recyclebin_date` (`date`)
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
  `key` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin default '',
  `index` int(11) unsigned DEFAULT '0',
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
  KEY `key` (`key`),
  KEY `index` (`index`),
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
  `localizedErrorDocuments` text,
  `redirectToMainDomain` tinyint(1) DEFAULT NULL,
  `creationDate` int(11) unsigned DEFAULT '0',
  `modificationDate` int(11) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rootId` (`rootId`),
  CONSTRAINT `fk_sites_documents` FOREIGN KEY (`rootId`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
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

DROP TABLE IF EXISTS `settings_store`;
CREATE TABLE `settings_store` (
  `id` varchar(190) NOT NULL DEFAULT '',
  `scope` varchar(190) NOT NULL DEFAULT '',
  `data` longtext,
  `type` enum('bool','int','float','string') NOT NULL DEFAULT 'string',
  PRIMARY KEY (`id`, `scope`),
  KEY `scope` (`scope`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `translations_messages`;
CREATE TABLE `translations_messages` (
  `key` varchar(190) NOT NULL DEFAULT '' COLLATE 'utf8mb4_bin',
  `type` varchar(10) DEFAULT NULL,
  `language` varchar(10) NOT NULL DEFAULT '',
  `text` text,
  `creationDate` int(11) unsigned DEFAULT NULL,
  `modificationDate` int(11) unsigned DEFAULT NULL,
  `userOwner` int(11) unsigned DEFAULT NULL,
  `userModification` int(11) unsigned DEFAULT NULL,
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
  `language` varchar(10) DEFAULT 'en',
  `datetimeLocale` varchar(10) DEFAULT '',
  `contentLanguages` LONGTEXT NULL,
  `admin` tinyint(1) unsigned DEFAULT '0',
  `active` tinyint(1) unsigned DEFAULT '1',
  `permissions` text,
  `roles` varchar(1000) DEFAULT NULL,
  `welcomescreen` tinyint(1) DEFAULT NULL,
  `closeWarning` tinyint(1) DEFAULT NULL,
  `memorizeTabs` tinyint(1) DEFAULT NULL,
  `allowDirtyClose` tinyint(1) unsigned DEFAULT '1',
  `docTypes` text DEFAULT NULL,
  `classes` text DEFAULT NULL,
  `twoFactorAuthentication` varchar(255) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
	`activePerspective` VARCHAR(255) NULL DEFAULT NULL,
	`perspectives` LONGTEXT NULL DEFAULT NULL,
	`websiteTranslationLanguagesEdit` LONGTEXT NULL DEFAULT NULL,
  `websiteTranslationLanguagesView` LONGTEXT NULL DEFAULT NULL,
  `lastLogin` int(11) unsigned DEFAULT NULL,
  `keyBindings` json NULL,
  `passwordRecoveryToken` varchar(290) DEFAULT NULL,
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
  `cpath` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL, /* path in utf8 (3-byte) using the full key length of 3072 bytes */
  `userId` int(11) unsigned NOT NULL DEFAULT '0',
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
  UNIQUE INDEX `cpath_userId` (`cpath`,`userId`),
  CONSTRAINT `fk_users_workspaces_asset_assets` FOREIGN KEY (`cid`) REFERENCES `assets` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_users_workspaces_asset_users` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `users_workspaces_document`;
CREATE TABLE `users_workspaces_document` (
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `cpath` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL, /* path in utf8 (3-byte) using the full key length of 3072 bytes */
  `userId` int(11) unsigned NOT NULL DEFAULT '0',
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
  UNIQUE INDEX `cpath_userId` (`cpath`,`userId`),
  CONSTRAINT `fk_users_workspaces_document_documents` FOREIGN KEY (`cid`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_users_workspaces_document_users` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `users_workspaces_object`;
CREATE TABLE `users_workspaces_object` (
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `cpath` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL, /* path in utf8 (3-byte) using the full key length of 3072 bytes */
  `userId` int(11) unsigned NOT NULL DEFAULT '0',
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
  UNIQUE INDEX `cpath_userId` (`cpath`,`userId`),
  CONSTRAINT `fk_users_workspaces_object_objects` FOREIGN KEY (`cid`) REFERENCES `objects` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_users_workspaces_object_users` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

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
  `autoSave` TINYINT(4) NOT NULL DEFAULT 0,
  `storageType` VARCHAR(5) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `cid` (`cid`),
  KEY `ctype_cid` (`ctype`, `cid`),
  KEY `date` (`date`),
  KEY `binaryFileHash` (`binaryFileHash`),
  KEY `autoSave` (`autoSave`),
  KEY `stackTrace` (`stackTrace`(1)),
  KEY `versionCount` (`versionCount`)
) DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `website_settings`;
CREATE TABLE `website_settings` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(190) NOT NULL DEFAULT '',
    `type` ENUM('text','document','asset','object','bool') DEFAULT NULL,
    `data` TEXT,
    `language` VARCHAR(15) NOT NULL DEFAULT '',
    `siteId` INT(11) UNSIGNED DEFAULT NULL,
    `creationDate` INT(11) UNSIGNED DEFAULT '0',
    `modificationDate` INT(11) UNSIGNED DEFAULT '0',
    PRIMARY KEY (`id`),
    INDEX `name` (`name`),
    INDEX `siteId` (`siteId`)
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
	`definition` json NULL,
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
	`config` json NULL,
	`description` LONGTEXT NULL,
	`creationDate` INT(11) NULL,
	`modificationDate` INT(11) NULL,
	`shareGlobally` TINYINT(1) NULL,
	`setAsFavourite` TINYINT(1) NULL,
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
	`gridConfigId` INT(11) NOT NULL,
	`searchType` VARCHAR(50) NOT NULL DEFAULT '',
	`type` enum('asset','object') NOT NULL DEFAULT 'object',
    PRIMARY KEY (`ownerId`, `classId`, `searchType`, `objectId`),
	INDEX `classId` (`classId`),
	INDEX `searchType` (`searchType`),
    INDEX `grid_config_id` (`gridConfigId`),
    CONSTRAINT `fk_gridconfig_favourites_gridconfigs` FOREIGN KEY (`gridConfigId`) REFERENCES `gridconfigs` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
DEFAULT CHARSET=utf8mb4;
;

DROP TABLE IF EXISTS `gridconfig_shares`;
CREATE TABLE `gridconfig_shares` (
	`gridConfigId` INT(11) NOT NULL,
	`sharedWithUserId` INT(11) NOT NULL,
	PRIMARY KEY (`gridConfigId`, `sharedWithUserId`),
	INDEX `sharedWithUserId` (`sharedWithUserId`),
    INDEX `grid_config_id` (`gridConfigId`),
    CONSTRAINT `fk_gridconfig_shares_gridconfigs` FOREIGN KEY (`gridConfigId`) REFERENCES `gridconfigs` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
DEFAULT CHARSET=utf8mb4;
;

DROP TABLE IF EXISTS `importconfigs`;
CREATE TABLE `importconfigs` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`ownerId` INT(11) NULL,
	`classId` VARCHAR(50) NULL DEFAULT NULL,
	`name` VARCHAR(50) NULL,
	`config` json NULL,
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
  `payload` LONGTEXT NULL,
  `isStudio` TINYINT(1) DEFAULT 0 NOT NULL, -- TODO: Remove with end of Classic-UI
  INDEX `recipient` (`recipient`)
) DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `object_url_slugs`;
CREATE TABLE `object_url_slugs` (
      `objectId` INT(11) UNSIGNED NOT NULL DEFAULT '0',
      `classId` VARCHAR(50) NOT NULL DEFAULT '0',
      `fieldname` VARCHAR(70) NOT NULL DEFAULT '0',
      `ownertype` ENUM('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
      `ownername` VARCHAR(70) NOT NULL DEFAULT '',
      `position` VARCHAR(70) NOT NULL DEFAULT '0',
      `slug` varchar(765) NOT NULL, /* slug in utf8mb4 (4-byte) using the full key length of 3072 bytes */
      `siteId` INT(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`slug`, `siteId`),
      INDEX `objectId` (`objectId`),
      INDEX `classId` (`classId`),
      INDEX `fieldname` (`fieldname`),
      INDEX `position` (`position`),
      INDEX `ownertype` (`ownertype`),
      INDEX `ownername` (`ownername`),
      INDEX `slug` (`slug`),
      INDEX `siteId` (`siteId`),
      INDEX `fieldname_ownertype_position_objectId` (`fieldname`,`ownertype`,`position`,`objectId`),
      CONSTRAINT `fk_object_url_slugs__objectId` FOREIGN KEY (`objectId`) REFERENCES objects (`id`) ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `webdav_locks`;
CREATE TABLE `webdav_locks` (
    id      INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    owner   VARCHAR(100),
    timeout INTEGER UNSIGNED,
    created INTEGER,
    token   VARBINARY(100),
    scope   TINYINT,
    depth   TINYINT,
    uri     VARBINARY(1000),
    INDEX (token),
    INDEX (uri(100))
) DEFAULT CHARSET = utf8mb4;
