CREATE TABLE IF NOT EXISTS `targeting_rules`
(
    `id`          int(11) NOT NULL AUTO_INCREMENT,
    `name`        varchar(255) NOT NULL DEFAULT '',
    `description` text,
    `scope`       varchar(50)           DEFAULT NULL,
    `active`      tinyint(1) DEFAULT NULL,
    `prio`        smallint(5) unsigned NOT NULL DEFAULT '0',
    `conditions`  longtext,
    `actions`     longtext,
    PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `targeting_storage`
(
    `visitorId`        varchar(100) NOT NULL,
    `scope`            varchar(50)  NOT NULL,
    `name`             varchar(100) NOT NULL,
    `value`            text,
    `creationDate`     datetime DEFAULT NULL,
    `modificationDate` datetime DEFAULT NULL,
    PRIMARY KEY (`visitorId`, `scope`, `name`),
    KEY                `targeting_storage_scope_index` (`scope`),
    KEY                `targeting_storage_name_index` (`name`)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `targeting_target_groups`
(
    `id`          int(11) NOT NULL AUTO_INCREMENT,
    `name`        varchar(255) NOT NULL DEFAULT '',
    `description` text,
    `threshold`   int(11) DEFAULT NULL,
    `active`      tinyint(1) DEFAULT NULL,
    PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;
