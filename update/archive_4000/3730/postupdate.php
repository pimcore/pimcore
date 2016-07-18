<?php

$db = \Pimcore\Db::get();

$db->query("ALTER TABLE `classificationstore_keys` CHANGE `type` `type` VARCHAR(255) NULL DEFAULT NULL;");