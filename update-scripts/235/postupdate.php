<?php
$db = \Pimcore\Db::get();
$db->query("REPLACE INTO `users_permission_definitions` (`key`) VALUES ('clear_fullpage_cache');");
