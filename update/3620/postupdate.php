<?php

// get db connection
try {
    $db = Pimcore_Resource::get();

    $db->query("ALTER TABLE `classificationstore_keys`
        ADD COLUMN `title` VARCHAR(255) NOT NULL DEFAULT '' AFTER `name`,
        ADD INDEX `name` (`name`),
        ADD INDEX `enabled` (`enabled`),
        ADD INDEX `type` (`type`);
    ");
} catch (\Exception $e) {

}
