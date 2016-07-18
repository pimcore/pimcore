<?php

// get db connection
$db = Pimcore_Resource::get();

try{
    $db->query("ALTER TABLE `users_workspaces_asset` CHANGE COLUMN `userId` `userId` int(11) NOT NULL DEFAULT 0;");
    $db->query("ALTER TABLE `users_workspaces_document` CHANGE COLUMN `userId` `userId` int(11) NOT NULL DEFAULT 0;");
    $db->query("ALTER TABLE `users_workspaces_object` CHANGE COLUMN `userId` `userId` int(11) NOT NULL DEFAULT 0;");
} catch (\Exception $e) {
    echo $e->getMessage();
}
