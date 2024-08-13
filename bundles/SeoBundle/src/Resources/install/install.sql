CREATE TABLE IF NOT EXISTS `http_error_log` (
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

CREATE TABLE IF NOT EXISTS `redirects` (
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