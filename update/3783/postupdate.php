<?php

$db = \Pimcore\Db::get();

$db->query("ALTER TABLE `classificationstore_relations`
	ADD COLUMN `mandatory` TINYINT(1) NULL DEFAULT NULL AFTER `sorter`;
");