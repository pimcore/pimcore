<?php

// get db connection
$db = Pimcore_Resource::get();

try {
    $db->query("ALTER TABLE `documents_hardlink` DROP COLUMN `inheritedPropertiesFromSource`;");
} catch (Exception $e) {
    echo $e->getMessage();
    echo "Please execute the following query manually: <br />";
    echo "ALTER TABLE `documents_hardlink` DROP COLUMN `inheritedPropertiesFromSource`;";
}
    
