<?php

// get db connection
$db = Pimcore_Resource::get();


try {

    $db->query("ALTER TABLE `search_backend_data`
      ADD INDEX `fullpath` (`fullpath`),
      ADD INDEX `maintype` (`maintype`),
      ADD INDEX `type` (`type`),
      ADD INDEX `subtype` (`subtype`),
      ADD INDEX `published` (`published`) ,
      ADD INDEX `id` (`id`),
      ADD FULLTEXT INDEX `data` (`data`),
      ADD FULLTEXT INDEX `fieldcollectiondata` (`fieldcollectiondata`),
      ADD FULLTEXT INDEX `localizeddata` (`localizeddata`),
      ADD FULLTEXT INDEX `properties` (`properties`),
      ADD FULLTEXT INDEX `fulltext` (`data`,`fieldcollectiondata`,`localizeddata`,`properties`,`fullpath`),
     ENGINE=MyISAM;");

} catch (Exception $e) {
    echo $e->getMessage();
}
    
