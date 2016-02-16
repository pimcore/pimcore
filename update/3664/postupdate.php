<?php

$db = Pimcore\Db::get();

$tables = $db->fetchAll("SHOW TABLES LIKE 'object_metadata_%'");

foreach ($tables as $table) {
    $t = current($table);

    // migrate the quantity value records
    $db->query("ALTER TABLE `" . $t . "` DROP PRIMARY KEY, ADD PRIMARY KEY (`o_id`, `dest_id`, `type`, `fieldname`, `column`, `ownertype`, `ownername`, `position`);");
}


