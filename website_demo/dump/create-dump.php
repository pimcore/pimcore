<?php

include_once("../../pimcore/cli/startup.php");

$backup = new Pimcore_Backup($backupFile);
$initInfo = $backup->init();


$stepMethodMapping = array(
    "mysql-tables" => "mysqlTables",
    "mysql" => "mysqlData"
);


if(empty($initInfo["errors"])) {
    foreach ($initInfo["steps"] as $step) {
        if(!is_array($step[1])) {
            $step[1] = array();
        }

        if(array_key_exists($step[0], $stepMethodMapping)) {

            // skip these tables
            if(in_array($step[1]["name"], array("tracking_events", "cache_tags"))) {
                continue;
            }

            verboseMessage("execute: " . $step[0] . " | with the following parameters: " . implode(",",$step[1]));
            $return = call_user_func_array(array($backup, $stepMethodMapping[$step[0]]), $step[1]);
            if($return["filesize"]) {
                verboseMessage("current filesize of the backup is: " . $return["filesize"]);
            }
        }
    }
}

// do some modifications
$dumpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql";
$dumpData = file_get_contents($dumpFile);

// remove user specific data
$dumpData = preg_replace("/DEFINER(.*)DEFINER/i", "", $dumpData);

file_put_contents($dumpFile, $dumpData);

verboseMessage("Dump is here: " . $dumpFile);

function verboseMessage ($m) {
        echo $m . "\n";
}
