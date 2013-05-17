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
            verboseMessage("execute: " . $step[0] . " | with the following parameters: " . implode(",",$step[1]));
            $return = call_user_func_array(array($backup, $stepMethodMapping[$step[0]]), $step[1]);
            if($return["filesize"]) {
                verboseMessage("current filesize of the backup is: " . $return["filesize"]);
            }
        }
    }
}


function verboseMessage ($m) {
        echo $m . "\n";
}
