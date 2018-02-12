<?php

$db = \Pimcore\Db::get();
$db->insert('users_permission_definitions', ['key' => 'admin_translations']);
