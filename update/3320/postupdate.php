<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `custom_layouts` ADD COLUMN `default` TINYINT NOT NULL DEFAULT 0");
