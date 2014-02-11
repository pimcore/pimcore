<?php

// get db connection
$db = Pimcore_Resource::get();

$script = "ALTER TABLE email_log
 CHANGE id id INT(10) UNSIGNED AUTO_INCREMENT NOT NULL;";

try {
    $db->query($script);
} catch (Exception $e) {
    echo $e->getMessage();
    echo "Please execute the following query manually: <br />";
    echo $script;
}
