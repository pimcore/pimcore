<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();
$db->query("ALTER TABLE `documents_page` ADD COLUMN `css` longtext NULL;");

