<?php

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE assets_metadata_predefined CHANGE `type` `type` ENUM('input', 'textarea', 'asset', 'document', 'object', 'date','select','checkbox');");
$db->query("ALTER TABLE assets_metadata CHANGE `type` `type` ENUM('input', 'textarea', 'asset', 'document', 'object', 'date','checkbox','select');");
$db->query("ALTER TABLE assets_metadata_predefined ADD `config` TEXT ASCII AFTER modificationDate;");
