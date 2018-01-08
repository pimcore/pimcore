<?php

$db = \Pimcore\Db::get();
$db->insert('users_permission_definitions', ['key' => 'share_configurations']);
$db->insert('users_permission_definitions', ['key' => 'gdpr_data_extractor']);
