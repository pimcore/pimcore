<?php

$db = \Pimcore\Db::get();
$db->insert('users_permission_definitions', ['key' => 'piwik_settings']);
$db->insert('users_permission_definitions', ['key' => 'piwik_reports']);
$db->insert('users_permission_definitions', ['key' => 'share_configurations']);
