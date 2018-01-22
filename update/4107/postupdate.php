<?php

$db = \Pimcore\Db::get();
$db->query('ALTER TABLE `email_log` ADD INDEX `sentDate` (`sentDate`, `id`);');
