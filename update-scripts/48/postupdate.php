<?php

$db = \Pimcore\Db::get();
$db->query("ALTER TABLE `schedule_tasks` ADD INDEX `version` (`version`);");

