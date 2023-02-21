CREATE TABLE IF NOT EXISTS `glossary` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `language` varchar(10) DEFAULT NULL,
    `casesensitive` tinyint(1) DEFAULT NULL,
    `exactmatch` tinyint(1) DEFAULT NULL,
    `text` varchar(255) DEFAULT NULL,
    `link` varchar(255) DEFAULT NULL,
    `abbr` varchar(255) DEFAULT NULL,
    `site` int(11) unsigned DEFAULT NULL,
    `creationDate` int(11) unsigned DEFAULT '0',
    `modificationDate` int(11) unsigned DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `language` (`language`),
    KEY `site` (`site`)
) DEFAULT CHARSET=utf8mb4;
