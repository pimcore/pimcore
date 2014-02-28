<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `classes` ADD COLUMN `showVariants` TINYINT(1) NULL AFTER `propertyVisibility`;");


