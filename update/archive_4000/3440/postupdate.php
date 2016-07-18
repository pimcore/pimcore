<?php

$db = \Pimcore\Resource::get();

$db->query("ALTER TABLE `assets` ADD UNIQUE INDEX `fullpath` (`path`,`filename`)");
$db->query("ALTER TABLE `documents` ADD UNIQUE INDEX `fullpath` (`path`,`key`)");
$db->query("ALTER TABLE `objects` ADD UNIQUE INDEX `fullpath` (`o_path`,`o_key`)");
