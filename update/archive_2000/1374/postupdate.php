<?php

// get db connection
$db = Pimcore_Resource::get();


try {

    $db->exec("ALTER TABLE `dependencies` ADD INDEX `sourcetype` (`sourcetype`);");
    $db->exec("ALTER TABLE `dependencies` ADD INDEX `targettype` (`targettype`);");
    //$db->exec("");

} catch (Exception $e) {
    echo $e->getMessage();
}
    
