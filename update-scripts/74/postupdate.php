<?php

$db = \Pimcore\Db::get();
$db->query("ALTER TABLE `documents_link`
	CHANGE COLUMN `internalType` `internalType` ENUM('document','asset','object') NULL DEFAULT NULL AFTER `id`;
");
