<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("DELETE FROM `users_permission_definitions` WHERE `key`='bounce_mail_inbox';");
$db->query("INSERT INTO `users_permission_definitions` SET `key`='emails';");
