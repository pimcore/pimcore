<?php

// PHP 5.5 Crypt-API password compatibility layer for PHP version < PHP 5.5
include_once("password_compatibility.php");

$db = Pimcore_Resource::get();
$users = $db->fetchAll("SELECT id,password FROM users WHERE name != 'system' AND type = 'user' AND IFNULL(password, '') != ''");

foreach ($users as $user) {

    $newPassword = password_hash($user["password"], PASSWORD_DEFAULT);

    $db->update("users", [
        "password" => $newPassword
    ], "id = " . $user["id"]);
}
