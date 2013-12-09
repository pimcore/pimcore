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

$tableNames = array(
    "documents_doctypes",
    "glossary",
    "keyvalue_groups",
    "keyvalue_keys",
    "properties_predefined",
    "redirects",
    "sites",
    "staticroutes"
);

foreach ($tableNames as $tableName) {
    sendQuery("ALTER TABLE `" . $tableName . "` ADD COLUMN `creationDate` bigint(20) unsigned DEFAULT 0;");
    sendQuery("ALTER TABLE `" . $tableName . "` ADD COLUMN `modificationDate` bigint(20) unsigned DEFAULT 0;");
}
