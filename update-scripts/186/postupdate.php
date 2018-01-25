<?php

$db = \Pimcore\Db::get();
$websiteSettings = $db->fetchAll('SELECT * FROM website_settings');

if (!$websiteSettings) {
    $websiteSettings = [];
}

$file = \Pimcore\Config::locateConfigFile('website-settings.php');
$table = \Pimcore\Db\PhpArrayFileTable::get($file);
$table->truncate();

foreach ($websiteSettings as $websiteSetting) {
    unset($websiteSetting['id']);
    $table->insertOrUpdate($websiteSetting, $websiteSetting['id']);
}

$db->query('RENAME TABLE `website_settings` TO `PLEASE_DELETE__website_settings`;');
