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

sendQuery("ALTER TABLE `documents_page` ADD COLUMN `prettyUrl` varchar(255) NULL DEFAULT NULL;");
sendQuery("ALTER TABLE `documents_page` ADD UNIQUE INDEX `prettyUrl` (`prettyUrl`(255));");

?>