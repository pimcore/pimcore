<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("INSERT INTO `users_permission_definitions` VALUES ('document_style_editor');");
$db->query("INSERT INTO `users_permission_definitions` VALUES ('recyclebin');");
$db->query("INSERT INTO `users_permission_definitions` VALUES ('seo_document_editor');");
$db->query("INSERT INTO `users_permission_definitions` VALUES ('robots.txt');");
$db->query("INSERT INTO `users_permission_definitions` VALUES ('http_errors');");
$db->query("INSERT INTO `users_permission_definitions` VALUES ('tag_snippet_management');");
$db->query("INSERT INTO `users_permission_definitions` VALUES ('qr_codes');");
$db->query("INSERT INTO `users_permission_definitions` VALUES ('targeting');");
$db->query("INSERT INTO `users_permission_definitions` VALUES ('notes_events');");
$db->query("INSERT INTO `users_permission_definitions` VALUES ('backup');");
$db->query("INSERT INTO `users_permission_definitions` VALUES ('bounce_mail_inbox');");
$db->query("INSERT INTO `users_permission_definitions` VALUES ('website_settings');");
