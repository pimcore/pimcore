<?php

    // get db connection
    $db = Pimcore_Resource_Mysql::get("database");
    $db->getConnection()->exec("
        CREATE TABLE `cache_tags` (
          `id` varchar(255) NOT NULL DEFAULT '',
          `tag` varchar(255) NULL DEFAULT NULL,
          PRIMARY KEY (`id`(255),`tag`(255)),
          INDEX `id` (`id`(255)),
          INDEX `tag` (`tag`(255))
        ) ENGINE=InnoDB;
    ");
    
?>

Now with memcached backend, see <a href="http://www.pimcore.org/documentation/developer/general/cache">cache documentation</a> for more information.