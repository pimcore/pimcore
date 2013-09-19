<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `targeting_personas` ADD COLUMN `threshold` int(11) NULL DEFAULT NULL;");
