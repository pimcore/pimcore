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

sendQuery("ALTER TABLE `search_backend_data` DROP INDEX `fulltext`;");
sendQuery("ALTER TABLE `search_backend_data` DROP INDEX `fieldcollectiondata`;");
sendQuery("ALTER TABLE `search_backend_data` DROP INDEX `localizeddata`;");
sendQuery("ALTER TABLE `search_backend_data` DROP COLUMN `fieldcollectiondata`;");
sendQuery("ALTER TABLE `search_backend_data` DROP COLUMN `localizeddata`;");
sendQuery("ALTER TABLE `search_backend_data` ADD FULLTEXT INDEX `fulltext` (`data`,`properties`,`fullpath`);");


?>

<b style="color: red;">Please execute the script /pimcore/cli/search-backend-reindex.php manually on the commandline!</b>

