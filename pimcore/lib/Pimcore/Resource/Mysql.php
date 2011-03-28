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

class Pimcore_Resource_Mysql {

    public static function getConnection () {

        $conf = Zend_Registry::get("pimcore_config_system");

        $db = Zend_Db::factory($conf->database);
        $db->getConnection()->exec("SET NAMES UTF8");

        return $db;
    }

    public static function reset(){  

        try {
            $db = self::getConnection();

            Zend_Registry::set("Pimcore_Resource_Mysql", $db);

            if(PIMCORE_DEVMODE) {
                $profiler = new Pimcore_Resource_Mysql_Profiler('All DB Queries');
                $profiler->setEnabled(true);
                $db->setProfiler($profiler);
            }

            return $db;
        }
        catch (Exception $e) {

            $errorMessage = "Unable to establish the database connection with the given configuration in /website/var/config/system.xml, for details see the debug.log";

            Logger::emergency($errorMessage);
            Logger::emergency($e);
            die($errorMessage);
        }
    }


    public static function get() {
        try {
            $connection = Zend_Registry::get("Pimcore_Resource_Mysql");
            return $connection;
        }
        catch (Exception $e) {
            return self::reset();
        }
    }

    public static function set($connection) {
        Zend_Registry::set("Pimcore_Resource_Mysql", $connection);
    }

}
