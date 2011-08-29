<?php

// get db connection
$db = Pimcore_Resource::get();


try {

    $db->query("ALTER TABLE `search_backend_data` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

} catch (Exception $e) {
    echo $e->getMessage();
}
    
