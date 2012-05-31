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

include_once("startup.php");    


try {
    $opts = new Zend_Console_Getopt(array(
        'job|j=s' => 'call just a specific job(s), use "," (comma) to execute more than one job (valid options: scheduledtasks, logmaintenance, sanitycheck, cleanupoldpidfiles, versioncleanup, redirectcleanup, cleanupbrokenviews, and plugin classes if you want to call a plugin maintenance)',
        'manager|m=s' => 'force a specific manager (valid options: procedural, daemon)',
        'ignore-maintenance-mode' => 'forces the script execution even when the maintenance mode is activated',
        'verbose|v' => 'show detailed information during the maintenance (for debug, ...)',
        'help|h' => 'display this help'
    ));
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
    $writer = new Zend_Log_Writer_Stream('php://output');
    $logger = new Zend_Log($writer);
    Logger::addLogger($logger);

    // set all priorities
    Logger::setPriorities(array(
        Zend_Log::DEBUG,
        Zend_Log::INFO,
        Zend_Log::NOTICE,
        Zend_Log::WARN,
        Zend_Log::ERR,
        Zend_Log::CRIT,
        Zend_Log::ALERT,
        Zend_Log::EMERG
    ));
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

// register scheduled tasks
$manager->registerJob(new Schedule_Maintenance_Job("scheduledtasks", new Schedule_Task_Executor(), "execute"));
$manager->registerJob(new Schedule_Maintenance_Job("logmaintenance", new Pimcore_Log_Maintenance(), "mail"));
$manager->registerJob(new Schedule_Maintenance_Job("httperrorlog", new Pimcore_Log_Maintenance(), "httpErrorLogCleanup"));
$manager->registerJob(new Schedule_Maintenance_Job("sanitycheck", "Element_Service", "runSanityCheck"));
$manager->registerJob(new Schedule_Maintenance_Job("cleanupoldpidfiles", "Schedule_Manager_Factory", "cleanupOldPidFiles"), true);
$manager->registerJob(new Schedule_Maintenance_Job("versioncleanup", new Version(), "maintenanceCleanUp"));
$manager->registerJob(new Schedule_Maintenance_Job("redirectcleanup", "Redirect", "maintenanceCleanUp"));
$manager->registerJob(new Schedule_Maintenance_Job("cleanupbrokenviews", "Pimcore_Resource", "cleanupBrokenViews"));

// call plugins
$plugins = Pimcore_API_Plugin_Broker::getInstance()->getPlugins();
foreach ($plugins as $plugin) {
    $id = get_class($plugin);

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

