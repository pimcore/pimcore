<?php

// get db connection
$db = Pimcore_Resource::get();


$script = "CREATE TABLE `documents_email` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `controller` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `to` varchar(255) DEFAULT NULL,
  `from` varchar(255) DEFAULT NULL,
  `cc` varchar(255) DEFAULT NULL,
  `bcc` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;";

try {
    $db->query($script);
} catch (Exception $e) {
    echo $e->getMessage();
    echo "Please execute the following query manually: <br />";
    echo $script;
}


$script = "CREATE TABLE `email_log` (
  `id` int(10) unsigned NOT NULL,
  `documentId` int(11) DEFAULT NULL,
  `requestUri` varchar(255) DEFAULT NULL,
  `params` text,
  `from` varchar(255) DEFAULT NULL,
  `to` varchar(255) DEFAULT NULL,
  `cc` varchar(255) DEFAULT NULL,
  `bcc` varchar(255) DEFAULT NULL,
  `sentDate` bigint(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;";

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
