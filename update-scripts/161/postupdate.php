<?php

$db = \Pimcore\Db::get();
$db->query('ALTER TABLE `quantityvalue_units` MODIFY `abbreviation` varchar(20)');

// targeting
$db->query('RENAME TABLE targeting_personas TO targeting_target_groups');
$db->query('ALTER TABLE `targeting_rules` ADD `prio` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `active`');
$db->query('ALTER TABLE `targeting_target_groups` DROP `conditions`');
$db->query('ALTER TABLE `documents_page` CHANGE `personas` `targetGroupIds` VARCHAR(255)');
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
