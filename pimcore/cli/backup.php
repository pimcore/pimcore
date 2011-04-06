<?php 
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

echo "\n";

include_once("startup.php");

try {
    $opts = new Zend_Console_Getopt(array(
        'filename|f=s'    => 'filename for the backup (default: backup_m-d-Y_H-i) .tar is added automatically',
        'directory|d=s'   => 'target directory (absolute path without trailing slash) for the backup-file (default: ' . PIMCORE_BACKUP_DIRECTORY . ')',
        'overwrite|o' => 'overwrite existing backup with the same filename, default: true',
        'cleanup|c=s' => 'in days, backups in the target directory which are older than the given days will be deleted, default 7, use false to disable it',
        'verbose|v' => 'show detailed information during the backup',
        'help|h' => 'display this help'
    ));
} catch (Exception $e) {
    echo "There's a problem with your commandline interface, I will now create a backup with the default configuration.";
    echo "\n";
    echo "For details, see the error below:";
    echo "\n";
    echo $e->getMessage();
}


try {
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
    echo "There's a problem with your configuration, I will now create a backup with the default configuration.";
    echo "\n";
    echo "For details, see the error below:";
    echo "\n";
    echo $e->getMessage();
}


// defaults
$config = array(
    "filename" => "backup_" . date("m-d-Y_H-i"),
    "directory" => PIMCORE_BACKUP_DIRECTORY,
    "overwrite" => false,
    "cleanup" => 7,
    "verbose" => false
);


// display help message
if($opts->getOption("help")) {
    echo $opts->getUsageMessage();
    exit;
}

$tmpConfig = $config;
foreach ($config as $key => $value) {
    if($opts->getOption($key)) {
        $tmpConfig[$key] = $opts->getOption($key);
    }
}
$config = $tmpConfig;
Zend_Registry::set("config", $config);

$backupFile = $config["directory"] . "/" . $config["filename"] . ".tar";


// check for existing file
if(is_file($backupFile) && !$config["overwrite"]) {
    echo "backup-file already exists, please use --overwrite=true or -o true to overwrite it";
    exit;
} else if (is_file($backupFile)) {
    @unlink($backupFile);
}

// cleanup
if($config["cleanup"] != "false") {
    $files = scandir($config["directory"]);
    $lifetime = (int) $config["cleanup"] * 86400;
    foreach ($files as $file) {
        if(is_file($config["directory"] . "/" . $file) && preg_match("/\.tar$/",$file)) {
            if(filemtime($config["directory"] . "/" . $file) < (time()-$lifetime) ) {
                verboseMessage("delete: " . $config["directory"] . "/" . $file . "\n");
                unlink($config["directory"] . "/" . $file);
            }
        }
    }
}

verboseMessage("------------------------------------------------");
verboseMessage("------------------------------------------------");
verboseMessage("starting backup into file: " . $backupFile);


$backup = new Pimcore_Backup($backupFile);
$initInfo = $backup->init();

$stepMethodMapping = array(
    "mysql-tables" => "mysqlTables",
    "mysql" => "mysqlData",
    "mysql-complete" => "mysqlComplete",
    "files" => "fileStep",
    "complete" => "complete"
);

if(empty($initInfo["errors"])) {
    foreach ($initInfo["steps"] as $step) {
        if(!is_array($step[1])) {
            $step[1] = array();
        }
        verboseMessage("execute: " . $step[0] . " | with the following parameters: " . implode(",",$step[1]));
        $return = call_user_func_array(array($backup, $stepMethodMapping[$step[0]]), $step[1]);
        if($return["filesize"]) {
            verboseMessage("current filesize of the backup is: " . $return["filesize"]);
        }
    }
}



verboseMessage("------------------------------------------------");
verboseMessage("------------------------------------------------");
verboseMessage("backup finished, you can find your backup here: " . $backupFile);


function verboseMessage ($m) {
    $config = Zend_Registry::get("config");
    if($config["verbose"]) {
        echo $m . "\n";
    }
}
