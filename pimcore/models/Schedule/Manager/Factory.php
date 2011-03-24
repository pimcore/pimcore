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
 * @category   Pimcore
 * @package    Schedule
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Schedule_Manager_Factory {
    /**
     * @static
     * @param  string $pidFile
     * @return Schedule_Manager_Procedural|Schedule_Manager_Daemon
     */
    public static function getManager($pidFile){
        if(function_exists("pcntl_fork") and function_exists("pcntl_waitpid") and function_exists("pcntl_wexitstatus") and function_exists("pcntl_signal")){
            $manager = new Schedule_Manager_Daemon($pidFile);
        } else {
            $manager = new Schedule_Manager_Procedural($pidFile);
            Logger::debug("Schedule_Factory: pcntl does not seem to be fully available, continuing with procedural maintenance jobs");
        }
        return $manager;
    }
    
    
    public static function cleanupOldPidFiles () {

        $pidLifeTime = 86400;
        
        $files = scandir(PIMCORE_SYSTEM_TEMP_DIRECTORY);
        foreach ($files as $file) {
            if(is_file(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $file) && preg_match("/maintenance_(.*)\.pid$/",$file)) {
                if(filemtime(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $file) < (time()-$pidLifeTime)) { // remove all pids older than 24 hours
                    unlink(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $file);
                }
            }
        }
    }

}