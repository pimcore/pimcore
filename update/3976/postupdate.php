<?php

$db = \Pimcore\Db::get();
$db->update("properties", [
    "inheritable" => "0"
], "`name` LIKE 'navigation\\_%' AND `inheritable` = '1'");
