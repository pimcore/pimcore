<?php

// get db connection
$db = Pimcore_Resource::get();


try {
    $db->query("ALTER TABLE `cache_tags` ENGINE=MEMORY;");

} catch (Exception $e) {
    echo $e->getMessage();
}
    
