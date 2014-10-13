<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `email_log`
CHANGE `requestUri` `requestUri` varchar(500),
CHANGE `from` `from` varchar(500),
CHANGE `to` `to` longtext,
CHANGE `cc` `cc` longtext,
CHANGE `bcc` `bcc` longtext,
CHANGE `subject` `subject` varchar(500)");
