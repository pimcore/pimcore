<?php

$db = \Pimcore\Db::get();
$db->delete("users_permission_definitions", $db->quoteIdentifier("key") . " = 'newsletter'");
