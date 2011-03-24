<?php

    // get db connection
    $db = Pimcore_Resource_Mysql::get("database");
    $db->getConnection()->exec("CREATE TABLE `edit_lock` (
      `id` int(11) NOT NULL auto_increment,
      `cid` int(11) unsigned NOT NULL default '0',
      `ctype` enum('document','asset','object') collate utf8_bin default NULL,
      `userId` int(11) unsigned NOT NULL default '0',
      `sessionId` varchar(255) collate utf8_bin default NULL,
      `date` int(11) unsigned default NULL,
      PRIMARY KEY  (`id`),
      KEY `cid` (`cid`),
      KEY `ctype` (`ctype`),
      KEY `cidtype` (`cid`,`ctype`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");
    
    
?>


<b>Release Notes (228):</b>
<br />
- Locks for documents, assets and objects