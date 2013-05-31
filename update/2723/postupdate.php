<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `documents_page` ADD COLUMN `personas` varchar(255) NULL DEFAULT NULL;");
