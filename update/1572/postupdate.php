<?php

// get db connection
$db = Pimcore_Resource::get();

$script = "ALTER TABLE `email_log` CHANGE COLUMN `id` `id` int(10) unsigned NOT NULL AUTO_INCREMENT;";

try {
    $db->query($script);
} catch (Exception $e) {
    echo $e->getMessage();
    echo "Please execute the following query manually: <br />";
    echo $script;
}
