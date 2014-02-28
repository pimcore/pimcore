<?php

function sendQuery ($sql) {
    try {
        $db = Pimcore_Resource::get();
        $db->query($sql);
    } catch (Exception $e) {
        echo $e->getMessage();
        echo "Please execute the following query manually: <br />";
        echo "<pre>" . $sql . "</pre><hr />";
    }
}

sendQuery("DELETE FROM `users` WHERE hasCredentials != 1;");
sendQuery("ALTER TABLE `users` DROP COLUMN `hasCredentials`;");
sendQuery("UPDATE `users` SET `active` = 0 WHERE `admin` != 1;");
sendQuery("ALTER TABLE `users` ADD COLUMN `type` enum('user','userfolder','role','rolefolder') NOT NULL DEFAULT 'user' AFTER `parentId`;");
sendQuery("ALTER TABLE `users` CHANGE COLUMN `username` `name` varchar(50) NULL DEFAULT NULL;");
sendQuery("ALTER TABLE `users` ADD COLUMN `permissions` varchar(1000) NULL DEFAULT NULL;");
sendQuery("ALTER TABLE `users` ADD COLUMN `roles` varchar(1000) NULL DEFAULT NULL;");
sendQuery("DELETE FROM `users_permission_definitions` WHERE `key`='update';");
sendQuery("DELETE FROM `users_permission_definitions` WHERE `key`='users';");
sendQuery("DELETE FROM `users_permission_definitions` WHERE `key`='forms';");
sendQuery("ALTER TABLE `users_permission_definitions` DROP COLUMN `translation`;");
sendQuery("DROP TABLE `users_permissions`;");

sendQuery("CREATE TABLE `users_workspaces_asset` (
  `cid` int(11) unsigned DEFAULT NULL,
  `cpath` varchar(255) DEFAULT NULL,
  `userId` int(11) unsigned DEFAULT NULL,
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
) DEFAULT CHARSET=utf8;");

sendQuery("CREATE TABLE `users_workspaces_document` (
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
  `settings` tinyint(1) unsigned DEFAULT '0',
  `versions` tinyint(1) unsigned DEFAULT '0',
  `properties` tinyint(1) unsigned DEFAULT '0',
  PRIMARY KEY (`cid`, `userId`),
  KEY `cid` (`cid`),
  KEY `userId` (`userId`)
) DEFAULT CHARSET=utf8;");

sendQuery("CREATE TABLE `users_workspaces_object` (
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
  `settings` tinyint(1) unsigned DEFAULT '0',
  `versions` tinyint(1) unsigned DEFAULT '0',
  `properties` tinyint(1) unsigned DEFAULT '0',
  PRIMARY KEY (`cid`, `userId`),
  KEY `cid` (`cid`),
  KEY `userId` (`userId`)
) DEFAULT CHARSET=utf8;");


sendQuery("RENAME TABLE `assets_permissions` TO `PLEASE_DELETE__assets_permissions`;");
sendQuery("RENAME TABLE `documents_permissions` TO `PLEASE_DELETE__documents_permissions`;");
sendQuery("RENAME TABLE `objects_permissions` TO `PLEASE_DELETE__objects_permissions`;");

//sendQuery("");

?>