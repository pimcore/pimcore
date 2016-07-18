<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("INSERT INTO `users_permission_definitions` VALUES ('users');");
