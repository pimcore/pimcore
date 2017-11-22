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
