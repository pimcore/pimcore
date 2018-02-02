<?php

include_once('../../../pimcore/config/startup_cli.php');

// get tables which are already in install.sql
$installSql = file_get_contents(PIMCORE_PATH . '/lib/Pimcore/Install/Resources/install.sql');
preg_match_all('/CREATE TABLE `(.*)`/', $installSql, $matches);
$existingTables = $matches[1];

$db = \Pimcore\Db::get();
$databaseName = $db->getDatabase();

$tablesRaw = $db->fetchAll('SHOW FULL TABLES');

$views = [];
$tables = [];

foreach ($tablesRaw as $table) {
    if ($table['Table_type'] == 'VIEW') {
        $views[]  = current($table);
    } else {
        $tables[] = current($table);
    }
}

$dumpData = "\nSET NAMES utf8mb4;\n\n";

// dump table schema, without the tables in install.sql
foreach ($tables as $name) {
    if (in_array($name, $existingTables)) {
        continue;
    }

    $dumpData .= "\n\n";
    $dumpData .= 'DROP TABLE IF EXISTS `' . $name . '`;';
    $dumpData .= "\n";

    $tableData = $db->fetchRow('SHOW CREATE TABLE ' . $name);

    $dumpData .= $tableData['Create Table'] . ';';

    $dumpData .= "\n\n";
}

$dumpData .= "\n\n";

// dump data
foreach ($tables as $name) {
    if (in_array($name, ['tracking_events', 'cache', 'cache_tags', 'http_error_log', 'versions', 'edit_lock', 'locks', 'email_log', 'tmp_store'])) {
        continue;
    }

    $tableData = $db->fetchAll('SELECT * FROM ' . $name);

    foreach ($tableData as $row) {
        $cells = [];
        foreach ($row as $cell) {
            if (is_string($cell)) {
                $cell = $db->quote($cell);
            } elseif ($cell === null) {
                $cell = 'NULL';
            }

            $cells[] = $cell;
        }

        $dumpData .= 'INSERT INTO `' . $name . '` VALUES (' . implode(',', $cells) . ');';
        $dumpData .= "\n";
    }
}

foreach ($views as $name) {
    // dump view structure
    $dumpData .= "\n\n";
    $dumpData .= 'DROP VIEW IF EXISTS `' . $name . '`;';
    $dumpData .= "\n";

    try {
        $viewData = $db->fetchRow('SHOW CREATE VIEW ' . $name);
        $dumpData .= $viewData['Create View'] . ';';
    } catch (\Exception $e) {
        echo $e->getMessage() . "\n";
    }
}

// remove user specific data
$dumpData = preg_replace('/DEFINER(.*)DEFINER/i', '', $dumpData);
$dumpData .= "\n";

$finalDest = __DIR__ . '/data.sql';
file_put_contents($finalDest, $dumpData);

echo 'Dump is here: ' . $finalDest . "\n";
