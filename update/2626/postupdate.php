<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `documents` CHANGE COLUMN `index` `index` int(11) unsigned NULL DEFAULT 0;");

