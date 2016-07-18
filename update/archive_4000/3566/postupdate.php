<?php

// get db connection
$db = Pimcore_Resource::get();


$db->query("ALTER TABLE `quantityvalue_units`
	ADD COLUMN `conversionOffset` DOUBLE NULL DEFAULT NULL AFTER `factor`;
");
