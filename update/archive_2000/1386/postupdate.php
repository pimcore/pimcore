<?php

// get db connection
$db = Pimcore_Resource::get();


try {

    $db->query("ALTER TABLE `properties_predefined` ADD INDEX `name` (`name`(255));");
    $db->query("ALTER TABLE `properties_predefined` ADD INDEX `id` (`id`);");
    $db->query("ALTER TABLE `properties_predefined` ADD INDEX `key` (`key`(255));");
    $db->query("ALTER TABLE `properties_predefined` ADD INDEX `type` (`type`);");
    $db->query("ALTER TABLE `properties_predefined` ADD INDEX `ctype` (`ctype`);");
    $db->query("ALTER TABLE `properties_predefined` ADD INDEX `inheritable` (`inheritable`);");

} catch (Exception $e) {
    echo $e->getMessage();
}
    
