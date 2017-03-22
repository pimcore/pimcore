<?php

$db = \Pimcore\Db::get();
$db->query("ALTER TABLE `versions` ADD INDEX `date` (`date`);");

