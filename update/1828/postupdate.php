<?php

function sendQuery ($sql) {
    try {
        $db = Pimcore_Resource::get();
        $db->query($sql);
    } catch (Exception $e) {
        echo $e->getMessage();
        echo "Please execute the following query manually: <br />";
        echo "<pre>" . $sql . "</pre><hr />";
    }
}

sendQuery("ALTER TABLE `users` ADD COLUMN `closeWarning` tinyint(1) NULL DEFAULT NULL;");

$db = Pimcore_Resource::get();
$db->update("users", array("closeWarning" => 1), "type = 'user'");
