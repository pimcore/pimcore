<?php

$db = \Pimcore\Db::get();

$columns = $db->fetchAll("SHOW COLUMNS FROM documents_page");

foreach($columns as $column) {
    if($column["Field"] == "css") {
        $db->query("ALTER TABLE documents_page DROP COLUMN `css`;");
    }
}

\Pimcore\Cache::clearAll();
