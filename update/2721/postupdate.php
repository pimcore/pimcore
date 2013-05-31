<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("RENAME TABLE `targeting` TO `targeting_rules`;");
