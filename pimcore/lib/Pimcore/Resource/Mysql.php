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

    /**
     * @static
     * @return string
     */
    public static function getType () {
        return "mysql";
    }

    /**
     * @static
     * @return Zend_Db_Adapter_Abstract
     */
    public static function getConnection () {

        $charset = "UTF8";

        // explicit set charset for connection (to the adapter)
        $config = Pimcore_Config::getSystemConfig()->toArray();
        $config["database"]["params"]["charset"] = $charset;

        $db = Zend_Db::factory($config["database"]["adapter"],$config["database"]["params"]);
        $db->getConnection()->exec("SET NAMES " . $charset);

        // try to set innodb as default storage-engine
        try {
            $db->getConnection()->exec("SET storage_engine=InnoDB;");
        } catch (Exception $e) {
            Logger::warn($e);
        }

        if(PIMCORE_DEVMODE) {
            $profiler = new Pimcore_Resource_Mysql_Profiler('All DB Queries');
            $profiler->setEnabled(true);
            $db->setProfiler($profiler);
        }

        return $db;
    }

    /**
     * @static
     * @return Zend_Db_Adapter_Abstract
     */
    public static function reset(){

        // close old connections
        self::close();

        // get new connection
        try {
            $db = self::getConnection();
            self::set($db);

            return $db;
        }
        catch (Exception $e) {

            $errorMessage = "Unable to establish the database connection with the given configuration in /website/var/config/system.xml, for details see the debug.log";

            Logger::emergency($errorMessage);
            Logger::emergency($e);
            die($errorMessage);
        }
    }

    /**
     * @static
     * @return mixed|Zend_Db_Adapter_Abstract
     */
    public static function get() {

        try {
            if(Zend_Registry::isRegistered("Pimcore_Resource_Mysql")) {
                $connection = Zend_Registry::get("Pimcore_Resource_Mysql");
                if($connection instanceof Zend_Db_Adapter_Abstract) {
                    return $connection;
                }
            }
        }
        catch (Exception $e) {
            Logger::error($e);
        }

        return self::reset();
    }

    /**
     * @static
     * @param $connection
     * @return void
     */
    public static function set($connection) {
        Zend_Registry::set("Pimcore_Resource_Mysql", $connection);
    }

    /**
     * @static
     * @return void
     */
    public static function close () {
        try {
            if(Zend_Registry::isRegistered("Pimcore_Resource_Mysql")) {
                $db = Zend_Registry::get("Pimcore_Resource_Mysql");

                if($db instanceof Zend_Db_Adapter_Abstract) {
                    $db->closeConnection();
                }

                // set it explicit to null to be sure it can be removed by the GC
                self::set("Pimcore_Resource_Mysql", null);
            }
        } catch (Exception $e) {
            Logger::error($e);
        }
    }
}
