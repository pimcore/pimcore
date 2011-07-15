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

$manager = Schedule_Manager_Factory::getManager("maintenance.pid");

// register scheduled tasks
$manager->registerJob(new Schedule_Maintenance_Job("scheduledtasks", new Schedule_Task_Executor(), "execute"));
$manager->registerJob(new Schedule_Maintenance_Job("youtubepreview", new Asset_Video_Youtube(), "maintenanceupload"));
$manager->registerJob(new Schedule_Maintenance_Job("logmaintenance", new Pimcore_Log_Maintenance(), "execute"));
$manager->registerJob(new Schedule_Maintenance_Job("sanitycheck", "Element_Service", "runSanityCheck",array(false)));
$manager->registerJob(new Schedule_Maintenance_Job("cleanupoldpidfiles", "Schedule_Manager_Factory", "cleanupOldPidFiles"), true);
$manager->registerJob(new Schedule_Maintenance_Job("versioncleanup", new Version(), "maintenanceCleanUp"));

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

?>