<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

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
