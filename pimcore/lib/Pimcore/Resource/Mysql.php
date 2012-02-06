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
     * @var string
     */
    protected static $_sqlLogFilename;

    /**
     * @var bool
     */
    protected static $_logProfilerWasEnabled;

    /**
     * @var bool
     */
    protected static $_logCaptureActive = false;

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
        $db->query("SET NAMES " . $charset);

        // try to set innodb as default storage-engine
        try {
            $db->query("SET storage_engine=InnoDB;");
        } catch (Exception $e) {
            Logger::warn($e);
        }

        // enable the db-profiler if the devmode is on and there is no custom profiler set (eg. in system.xml)
        if(PIMCORE_DEVMODE && !$db->getProfiler()->getEnabled()) {
            $profiler = new Pimcore_Db_Profiler('All DB Queries');
            $profiler->setEnabled(true);
            $db->setProfiler($profiler);
        }

        // put the connection into a wrapper to handle connection timeouts, ...
        $db = new Pimcore_Resource_Wrapper($db);

        Logger::debug("Successfully established connection to MySQL-Server");

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

            $errorMessage = "Unable to establish the database connection with the given configuration in /website/var/config/system.xml, for details see the debug.log. \nReason: " . $e->getMessage();

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
                if($connection instanceof Pimcore_Resource_Wrapper) {
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

                if($db instanceof Pimcore_Resource_Wrapper) {
                    $db->closeConnection();
                }

                // set it explicit to null to be sure it can be removed by the GC
                self::set("Pimcore_Resource_Mysql", null);
            }
        } catch (Exception $e) {
            Logger::error($e);
        }
    }

    /**
     * @static
     * @param string $method
     * @param array $args
     */
    public static function startCapturingDefinitionModifications ($method, $args) {
        if($method == "query") {
            if(preg_match("/(ALTER|CREATE|DROP|RENAME|TRUNCATE)(.*)(DATABASE|EVENT|FUNCTION|PROCEDURE|TABLE|TABLESPACE|VIEW|INDEX|TRIGGER)/i",$args[0])) {
                self::logDefinitionModification($args[0]);
            }
        } else {
            $tablesToCheck = array("classes","users_permission_definitions");

            if(in_array($args[0], $tablesToCheck)) {
                self::$_logProfilerWasEnabled = self::get()->getProfiler()->getEnabled();
                self::get()->getProfiler()->setEnabled(true);
                self::$_logCaptureActive = true;
            }
        }
    }

    /**
     * @static
     *
     */
    public static function stopCapturingDefinitionModifications () {

        if(self::$_logCaptureActive) {
            $search = array();
            $replace = array();
            $query = self::get()->getProfiler()->getLastQueryProfile()->getQuery();
            $params = self::get()->getProfiler()->getLastQueryProfile()->getQueryParams();

            // @TODO named parameters
            if(!empty($params)) {
                for ($i=0; $i<count($params); $i++) {
                    $search[] = "?";
                    $replace[] = self::get()->quote($params[$i]);
                }
                $query = str_replace($search, $replace, $query);
            }

            self::logDefinitionModification($query);
            self::get()->getProfiler()->setEnabled(self::$_logProfilerWasEnabled);
        }

        self::$_logCaptureActive = false;
    }

    /**
     * @static
     * @param string $sql
     */
    public static function logDefinitionModification ($sql) {

        if(!self::$_sqlLogFilename) {
            self::$_sqlLogFilename = "db-change-log_". time() ."-" . uniqid() . ".sql";
        }

        // write sql change log for deploying to production system
        $sql .= "\n\n\n";

        $file = PIMCORE_SYSTEM_TEMP_DIRECTORY."/". self::$_sqlLogFilename;
        if(defined("PIMCORE_DB_CHANGELOG_DIRECTORY")) {
            $file = PIMCORE_DB_CHANGELOG_DIRECTORY."/" . self::$_sqlLogFilename;
        }

        $handle = fopen($file,"a");
        fwrite($handle, $sql);
        fclose($handle);
    }


    /**
     * The error handler is called by the wrapper if an error occurs during __call()
     *
     * @static
     * @throws Exception
     * @param string $method
     * @param array $args
     * @param Exception $exception
     * @return
     */
    public static function errorHandler ($method, $args, $exception) {

        $lowerErrorMessage = strtolower($exception->getMessage());

        // check if the mysql-connection is the problem (timeout issues, ...)
        if(strpos($lowerErrorMessage, "mysql server has gone away") !== false || strpos($lowerErrorMessage, "lost connection") !== false) {
            // wait a few seconds
            sleep(5);

            // the connection to the server has probably been lost, try to reconnect and call the method again
            try {
                Logger::info("the connection to the MySQL-Server has probably been lost, try to reconnect...");
                self::reset();
                Logger::info("Reconnecting to the MySQL-Server was successful, sending the command again to the server.");
                $r = self::get()->callResourceMethod($method, $args);
                return $r;
            } catch (Exception $e) {
                Logger::debug($e);
                throw $e;
            }
        }

        // no handling just log the exception and then throw it
        Logger::debug($exception);
        throw $exception;
    }
}
