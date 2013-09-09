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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

echo "\n";

include_once("startup.php");

try {
    $opts = new Zend_Console_Getopt(array(
        'backup-file|f=s'    => 'Full path to the backup file.',
        'verbose|v' => 'show detailed information during the backup',
        'help|h' => 'display this help',
    ));
} catch (Exception $e) {
    echo "There's a problem with your commandline interface.";
    echo "\n";
    echo "For details, see the error below:";
    echo "\n";
    echo $e->getMessage();
}


try {
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
    echo "There's a problem with your configuration.";
    echo "\n";
    echo "For details, see the error below:";
    echo "\n";
    echo $e->getMessage();
}

// display help message
if($opts->getOption("help")) {
    echo $opts->getUsageMessage();
    exit;
}

$config = array('verbose' => false);

$tmpConfig = $config;
foreach ($config as $key => $value) {
    if($opts->getOption($key)) {
        $tmpConfig[$key] = $opts->getOption($key);
    }
}
$config = $tmpConfig;
Zend_Registry::set("config", $config);


$backupFile = $opts->getOption('backup-file');
if(!$backupFile || !is_readable($backupFile)){
    throw new Exception("Backup file not provided or it isn't readable.");
}else{
    verboseMessage("------------------------------------------------");
    verboseMessage("starting restore of backup: " . $backupFile);

    $backup = new Pimcore_Backup($backupFile);
    $backup->restore();
}


verboseMessage("------------------------------------------------");
verboseMessage("backup restore finished successfully.");


function verboseMessage ($m) {
    $config = Zend_Registry::get("config");
    if($config["verbose"]) {
        echo $m . "\n";
    }
}
