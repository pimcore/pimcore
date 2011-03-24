<?php

// get db connection
$db = Pimcore_Resource_Mysql::get("database");
$db->getConnection()->exec("ALTER TABLE `documents_link` ADD COLUMN `parameters` varchar(255) NULL DEFAULT NULL;");
$db->getConnection()->exec("ALTER TABLE `documents_link` ADD COLUMN `anchor` varchar(255) NULL DEFAULT NULL;");
$db->getConnection()->exec("ALTER TABLE `documents_link` ADD COLUMN `title` varchar(255) NULL DEFAULT NULL;");
$db->getConnection()->exec("ALTER TABLE `documents_link` ADD COLUMN `accesskey` varchar(255) NULL DEFAULT NULL;");
$db->getConnection()->exec("ALTER TABLE `documents_link` ADD COLUMN `rel` varchar(255) NULL DEFAULT NULL;");
$db->getConnection()->exec("ALTER TABLE `documents_link` ADD COLUMN `tabindex` varchar(255) NULL DEFAULT NULL;");

?>

<b>Release Notes (236):</b>
<br />
- Advanced features for Links ($link->getHtml(), more attributes)<br />
<u>- Properties for Snippets and Links</u>
