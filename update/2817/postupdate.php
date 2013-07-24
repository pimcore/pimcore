<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `uuids` DROP COLUMN `subType`;");

