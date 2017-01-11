<?php

$db = \Pimcore\Db::get();


$pages = $db->fetchAll("SELECT id, metaData FROM documents_page WHERE LENGTH(metaData) > 6");

$tpl = '<meta %s="%s" "%s="%s">';
foreach($pages as &$page) {
    $metaData = unserialize($page["metaData"]);
    $meta = [];
    foreach($metaData as $record) {
        $meta[] = sprintf(
            $tpl,
            $record["idName"],
            htmlspecialchars($record["idValue"], ENT_COMPAT, "UTF-8"),
            $record["contentName"],
            htmlspecialchars($record["contentValue"], ENT_COMPAT, "UTF-8")
        );
    }

    $db->update("documents_page", [
        "metaData" => serialize($meta)
    ], "id = " . $page["id"]);
}

$db->update("documents_page", [
    "metaData" => ""
], "LENGTH(metaData) < 7");

