<?php

$db = \Pimcore\Resource::get();
$db->query("ALTER TABLE `assets` ADD INDEX `modificationDate` (`modificationDate`);");
$db->query("ALTER TABLE `documents` ADD INDEX `modificationDate` (`modificationDate`);");
$db->query("ALTER TABLE `objects` ADD INDEX `o_modificationDate` (`o_modificationDate`);");

