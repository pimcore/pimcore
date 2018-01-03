<?php

$db = \Pimcore\Db::get();
$db->query('ALTER TABLE `quantityvalue_units` MODIFY `abbreviation` varchar(20)');

// targeting
$db->query('RENAME TABLE targeting_personas TO targeting_target_groups');

$db->query('ALTER TABLE `targeting_target_groups` DROP `conditions`');
$db->query('ALTER TABLE `documents_page` CHANGE `personas` `targetGroupIds` VARCHAR(255)');

$db->query('RENAME TABLE targeting_rules TO PLEASE_DELETE__targeting_rules');
$db->query(<<<'EOF'
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
EOF
);

$db->query(<<<'EOF'
CREATE TABLE `targeting_storage` (
  `visitorId` varchar(100) NOT NULL,
  `scope` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` text,
  `creationDate` datetime DEFAULT NULL,
  `modificationDate` datetime DEFAULT NULL,
  PRIMARY KEY (`visitorId`,`scope`,`name`),
  KEY `targeting_storage_scope_index` (`scope`),
  KEY `targeting_storage_name_index` (`name`),
  KEY `targeting_storage_visitorId_index` (`visitorId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
EOF
);
