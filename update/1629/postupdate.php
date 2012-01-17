<?php

function sendQuery ($sql) {
    try {
        $db = Pimcore_Resource::get();
        $db->query($sql);
    } catch (Exception $e) {
        echo $e->getMessage();
        echo "Please execute the following query manually: <br />";
        echo $sql;
    }
}

sendQuery("DELETE FROM `users` WHERE hasCredentials != 1;");
sendQuery("ALTER TABLE `users` DROP COLUMN `hasCredentials`;");
sendQuery("ALTER TABLE `users` ADD COLUMN `type` enum('user','userfolder','role','rolefolder') NOT NULL DEFAULT 'user' AFTER `parentId`;");
sendQuery("ALTER TABLE `users` CHANGE COLUMN `username` `name` varchar(50) NULL DEFAULT NULL;");
sendQuery("DELETE FROM `users_permission_definitions` WHERE `key`='update';");
sendQuery("DELETE FROM `users_permission_definitions` WHERE `key`='users';");
sendQuery("DELETE FROM `users_permission_definitions` WHERE `key`='forms';");
//sendQuery("");

?>