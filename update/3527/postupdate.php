<?php

// get db connection
$db = Pimcore_Resource::get();

$db->query("DELETE FROM `users_permission_definitions` WHERE  `key`='sent_emails';");
