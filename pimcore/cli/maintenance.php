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

include_once("startup.php");    

try {
    $optsConfig = array(
        'job|j=s' => 'call just a specific job(s), use "," (comma) to execute more than one job (valid options: scheduledtasks, cleanupcache, logmaintenance, sanitycheck, cleanuplogfiles, versioncleanup, redirectcleanup, cleanupbrokenviews, contentanalysis, usagestatistics, downloadmaxminddb and plugin classes if you want to call a plugin maintenance)',
        'manager|m=s' => 'force a specific manager (valid options: procedural, daemon)',
        'ignore-maintenance-mode' => 'forces the script execution even when the maintenance mode is activated',
        'verbose|v' => 'show detailed information during the maintenance (for debug, ...)',
        "force|f" => "run the jobs, regardless if they're locked or not",
        'help|h' => 'display this help'
    );

    // parse existing valid arguments => needed to do not add them twice => see below (dynamic add)
    $existingParams = array();
    foreach ($optsConfig as $key => $value) {
        foreach(explode("|",$key) as $v) {
            $existingParams[] = $v;
        }
    }

    // dynamically add non recognized options to avoid error messages
    $arguments = $_SERVER['argv'];
    array_shift($arguments);
    foreach ($arguments as $arg) {
        $arg = preg_match("/\-\-([a-zA-Z0-9]+)?(=| )?/", $arg, $matches);
        if(array_key_exists(1, $matches) && !in_array($matches[1], $existingParams)) {
            $optsConfig[$matches[1]] = "custom parameter";
        }
    }

    $opts = new Zend_Console_Getopt($optsConfig);

} catch (Exception $e) {
    echo $e->getMessage();
}

try {
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
    echo $e->getMessage();
}


// display help message
if($opts->getOption("help")) {
    echo $opts->getUsageMessage();
    exit;
}

if($opts->getOption("verbose")) {
    $writer = new Zend_Log_Writer_Stream('php://stdout');
    $logger = new Zend_Log($writer);
    Logger::addLogger($logger);

    // set all priorities
    Logger::setVerbosePriorities();
}

$forceType = null;
if($opts->getOption("manager")) {
    $forceType = $opts->getOption("manager");
}

$validJobs = array();
if($opts->getOption("job")) {
    $validJobs = explode(",",$opts->getOption("job"));
}

// create manager
$manager = Schedule_Manager_Factory::getManager("maintenance.pid", $forceType);
$manager->setValidJobs($validJobs);
$manager->setForce((bool) $opts->getOption("force"));

// register scheduled tasks
$manager->registerJob(new Schedule_Maintenance_Job("scheduledtasks", new Schedule_Task_Executor(), "execute"));
$manager->registerJob(new Schedule_Maintenance_Job("logmaintenance", new Pimcore_Log_Maintenance(), "mail"));
$manager->registerJob(new Schedule_Maintenance_Job("cleanuplogfiles", new Pimcore_Log_Maintenance(), "cleanupLogFiles"));
$manager->registerJob(new Schedule_Maintenance_Job("httperrorlog", new Pimcore_Log_Maintenance(), "httpErrorLogCleanup"));
$manager->registerJob(new Schedule_Maintenance_Job("usagestatistics", new Pimcore_Log_Maintenance(), "usageStatistics"));
$manager->registerJob(new Schedule_Maintenance_Job("sanitycheck", "Element_Service", "runSanityCheck"));
$manager->registerJob(new Schedule_Maintenance_Job("versioncleanup", new Version(), "maintenanceCleanUp"));
$manager->registerJob(new Schedule_Maintenance_Job("redirectcleanup", "Redirect", "maintenanceCleanUp"));
$manager->registerJob(new Schedule_Maintenance_Job("cleanupbrokenviews", "Pimcore_Resource", "cleanupBrokenViews"));
$manager->registerJob(new Schedule_Maintenance_Job("contentanalysis", "Tool_ContentAnalysis", "run"));
$manager->registerJob(new Schedule_Maintenance_Job("downloadmaxminddb", "Pimcore_Update", "updateMaxmindDb"));
$manager->registerJob(new Schedule_Maintenance_Job("cleanupcache", "Pimcore_Model_Cache", "maintenance"));

// call plugins
$plugins = array_merge(Pimcore_API_Plugin_Broker::getInstance()->getPlugins(), Pimcore_API_Plugin_Broker::getInstance()->getModules());
foreach ($plugins as $plugin) {
    $id = str_replace('\\', '_', get_class($plugin));

    $jobRegistered = null;

    if(method_exists($plugin, "maintenanceForce")) {
             $jobRegistered =  $manager->registerJob(new Schedule_Maintenance_Job($id, $plugin, "maintenanceForce"), true);
    } else {
            if(method_exists($plugin, "maintainance")) {
                    //legacy hack
                    $jobRegistered = $manager->registerJob(new Schedule_Maintenance_Job($id, $plugin, "maintainance"));
            } else  if(method_exists($plugin, "maintenance")) {
                    $jobRegistered = $manager->registerJob(new Schedule_Maintenance_Job($id, $plugin, "maintenance"));
            }
    }
}

$manager->run();

