<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();

$db->query("ALTER TABLE `documents_page` ADD COLUMN `contentMasterDocumentId` int(11) NULL DEFAULT NULL;");
$db->query("ALTER TABLE `documents_snippet` ADD COLUMN `contentMasterDocumentId` int(11) NULL DEFAULT NULL;");

