<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("DROP TABLE IF EXISTS `session`;");
