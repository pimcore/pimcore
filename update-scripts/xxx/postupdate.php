<?php

// TODO move to correct build number!

$db     = \Pimcore\Db::get();
$schema = $db->getSchemaManager()->createSchema();

if (!$schema->hasTable('targeting_target_groups') && $schema->hasTable('targeting_personas')) {
    $db->query('RENAME TABLE targeting_personas TO targeting_target_groups');
}

// new schema after table rename
$schema = $db->getSchemaManager()->createSchema();

if (!$schema->getTable('targeting_rules')->hasColumn('prio')) {
    $db->query('ALTER TABLE `targeting_rules` ADD `prio` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `active`');
}

if ($schema->getTable('targeting_target_groups')->hasColumn('conditions')) {
    $db->query('ALTER TABLE `targeting_target_groups` DROP `conditions`');
}

$documentsPageTable = $schema->getTable('documents_page');
if ($documentsPageTable->hasColumn('personas') && !$documentsPageTable->hasColumn('targetGroupIds')) {
    $db->query('ALTER TABLE `documents_page` CHANGE `personas` `targetGroupIds` VARCHAR(255)');
}

if (!$schema->hasTable('targeting_storage')) {
    Db::get()->query(<<<'EOF'
CREATE TABLE `targeting_storage` (
  `visitorId` varchar(255) NOT NULL,
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
}
