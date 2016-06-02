<?php

$db = \Pimcore\Db::get();

$db->query("INSERT INTO `users_permission_definitions` VALUES ('web2print_settings');");