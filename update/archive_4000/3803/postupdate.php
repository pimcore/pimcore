<?php

$db = \Pimcore\Db::get();
$keywords = $db->fetchAll("SELECT id,keywords FROM documents_page WHERE LENGTH(IFNULL(keywords,'')) > 0");

$keywordList = "";
foreach($keywords as $keyword) {
    $keywordList .= $keyword["id"] . "|" . $keyword["keywords"] . "\n";
}

\Pimcore\File::put(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/document-page-keyword-export.csv", $keywordList);

$db->query("ALTER TABLE documents_page DROP COLUMN keywords");


