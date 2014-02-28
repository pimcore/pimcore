<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `redirects` ADD COLUMN `passThroughParameters` tinyint(1) NULL DEFAULT NULL AFTER `sourceSite`;");

