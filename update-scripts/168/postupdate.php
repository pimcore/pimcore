<?php

$db = \Pimcore\Db::get();
$db->query('ALTER TABLE email_log ADD COLUMN `replyTo` VARCHAR(255) DEFAULT NULL AFTER `from`;');
$db->query('ALTER TABLE documents_email ADD COLUMN `replyTo` VARCHAR(255) DEFAULT NULL AFTER `from`;');
