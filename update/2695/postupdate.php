<?php

// get db connection
$db = Pimcore_Resource::get();
foreach(array('translations_website','translations_admin') as $type){
    $db->query('ALTER TABLE ' . $type .' CHANGE `date` `creationDate` BIGINT(20) UNSIGNED NULL DEFAULT NULL');
    $db->query('ALTER TABLE ' . $type .' ADD `modificationDate` BIGINT(20) UNSIGNED NULL DEFAULT NULL');
    $db->query('UPDATE ' . $type .' SET modificationDate = creationDate');
    Translation_Website::clearDependentCache();
}