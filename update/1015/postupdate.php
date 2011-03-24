<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->exec("
ALTER TABLE `translations_website`
  CHANGE COLUMN `id` `id` int(11) unsigned NOT NULL,
  DROP PRIMARY KEY,
  DROP INDEX `tid`,
  DROP INDEX `tid_language`;
");

$db->exec("
ALTER TABLE `translations_website`
  DROP COLUMN `id`,
  DROP COLUMN `tid`;
");

$db->exec("
ALTER TABLE `translations_website`
  ADD PRIMARY KEY (`key`(255),`language`(10)),
  DROP INDEX `key_language`;
");

$db->exec("
ALTER TABLE `translations_admin`
  CHANGE COLUMN `id` `id` int(11) unsigned NOT NULL,
  DROP PRIMARY KEY,
  DROP INDEX `tid`,
  DROP INDEX `tid_language`;
");

$db->exec("
ALTER TABLE `translations_admin`
  DROP COLUMN `id`,
  DROP COLUMN `tid`;
");

$db->exec("
ALTER TABLE `translations_admin`
  ADD PRIMARY KEY (`key`(255),`language`(10)),
  DROP INDEX `key_language`;
");




?>