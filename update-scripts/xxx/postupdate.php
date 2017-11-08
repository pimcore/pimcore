<?php

// TODO move to correct build number!

$db     = \Pimcore\Db::get();
$schema = $db->getSchemaManager()->createSchema();

if (!$schema->getTable('targeting_rules')->hasColumn('prio')) {
    $db->query('ALTER TABLE `targeting_rules` ADD `prio` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `active`');
}
