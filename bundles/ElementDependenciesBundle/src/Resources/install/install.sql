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