<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `search_backend_data` CHANGE COLUMN `fullpath` `fullpath` varchar(330) NULL DEFAULT NULL;");
