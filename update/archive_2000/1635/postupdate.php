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

sendQuery("ALTER TABLE `users` DROP INDEX `name`;");
sendQuery("ALTER TABLE `users` ADD UNIQUE INDEX `type_name` (`type`,`name`);");
sendQuery("ALTER TABLE `users` ADD INDEX `name` (`name`);");
sendQuery("ALTER TABLE `users` ADD INDEX `password` (`password`);");

?>