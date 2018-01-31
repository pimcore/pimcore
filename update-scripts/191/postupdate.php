<?php

try {
    $db = \Pimcore\Db::get();
    $db->insert('users_permission_definitions', ['key' => 'asset_metadata']);
} catch (\Exception $e) {
}
