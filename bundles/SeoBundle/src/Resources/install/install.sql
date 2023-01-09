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