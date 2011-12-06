<?php

// get db connection
$db = Pimcore_Resource::get();


$script = "CREATE TABLE `documents_email` (`id` int(11) unsigned NOT NULL default '0',`controller` varchar(255) default NULL,`action` varchar(255) default NULL,`template` varchar(255) default NULL,`to` varchar(255) default NULL,`from` varchar(255) default NULL,`cc` varchar(255) default NULL,`bcc` varchar(255) default NULL,`subject` varchar(255) default NULL,PRIMARY KEY  (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

try {
    $db->query($script);
} catch (Exception $e) {
    echo $e->getMessage();
    echo "Please execute the following query manually: <br />";
    echo $script;
}


$script = "CREATE TABLE `email_log` (`id` int(10) unsigned NOT NULL auto_increment COMMENT '  ',`documentId` int(11) default NULL,`requestUri` varchar(255) collate utf8_unicode_ci default NULL,`params` text collate utf8_unicode_ci,`from` varchar(255) collate utf8_unicode_ci default NULL,`to` varchar(255) collate utf8_unicode_ci default NULL,`cc` varchar(255) collate utf8_unicode_ci default NULL,`bcc` varchar(255) collate utf8_unicode_ci default NULL,`sentDate` bigint(20) default NULL,`subject` varchar(255) collate utf8_unicode_ci default NULL,PRIMARY KEY  (`id`)) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

try {
    $db->query($script);
} catch (Exception $e) {
    echo $e->getMessage();
    echo "Please execute the following query manually: <br />";
    echo $script;
}

$script = "ALTER TABLE documents_doctypes CHANGE type type ENUM('page', 'snippet','email');";

try {
    $db->query($script);
} catch (Exception $e) {
    echo $e->getMessage();
    echo "Please execute the following query manually: <br />";
    echo $script;
}



$script = "ALTER TABLE documents CHANGE type type ENUM('page', 'link', 'snippet', 'folder', 'hardlink','email');";

try {
    $db->query($script);
} catch (Exception $e) {
    echo $e->getMessage();
    echo "Please execute the following query manually: <br />";
    echo $script;
}
