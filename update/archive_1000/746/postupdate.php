<?php

include_once("simple_html_dom.php");

$db = Pimcore_Resource_Mysql::get("database");
$wysiwygFields = $db->fetchAll("SELECT * FROM documents_elements WHERE `type` = 'wysiwyg'");
$db->delete("documents_elements", "`type` = 'wysiwyg'");

foreach ($wysiwygFields as $field) {
    
    $html = str_get_html($field["data"]);
    $s = $html->find("a, img");
    
    foreach ($s as $el) {
        if ($el->src) {
            if (preg_match("/asset:([0-9]+)/i", $el->src, $match)) {
                if ($asset = Asset::getById($match[1])) {
                    $el->pimcore_id = $asset->getId();
                    $el->pimcore_type = "asset";
                    $el->src = $asset->getFullPath();
                }
                else {
                    $el->outertext = "";
                }
            }
        }
        if ($el->href) {
            if (preg_match_all("/(asset|document):([0-9]+)/i", $el->href, $match)) {
                if ($match[1][0] == "asset") {
                    if ($asset = Asset::getById($match[2][0])) {
                        $el->pimcore_id = $asset->getId();
                        $el->pimcore_type = "asset";
                        $el->href = $asset->getFullPath();
                    }
                    else {
                        $el->outertext = $el->innertext;
                    }
                }
                else if ($match[1][0] == "document") {
                    if ($doc = Document::getById($match[2][0])) {
                        $el->pimcore_id = $doc->getId();
                        $el->pimcore_type = "document";
                        $el->href = $doc->getFullPath();
                    }
                    else {
                        $el->outertext = $el->innertext;
                    }
                }
            }
        }
    }
    $field["data"] = $html->save();
    
    $db->insert("documents_elements",$field);
}
        
        
?>