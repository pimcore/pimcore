<?php

// get db connection
$db = Pimcore_Resource_Mysql::get();


// rename column "type" to "ctype" and create the new columns "type" and "position"
$tables = $db->fetchAll("SHOW FULL TABLES");

foreach ($tables as $table) {
    $name = current($table);
    $type = next($table);

    if(strtolower($type) != "view") {
        $db->exec("ALTER TABLE `" . $name . "` ENGINE=InnoDB, DEFAULT CHARSET=utf8;");
    }
}
