<?php

    // get db connection
    $db = Pimcore_Resource_Mysql::get("database");
    $db->getConnection()->exec("ALTER TABLE `versions` ADD COLUMN `public` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER `date`;");
    
    
?>


<b>Release Notes (226):</b>
<br />
- public accessable document versions (for A/B tests, ...)<br />
- notes for versions