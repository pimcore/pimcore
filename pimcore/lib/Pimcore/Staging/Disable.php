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

class Pimcore_Staging_Disable {
    
    public $dbTablesLive = array();
    public $dbTablesStage = array();
    public $dbViewsLive = array();
    public $dbViewsStage = array();
    public $id;


    protected function getStagingConfig () {
        return Pimcore_Config::getSystemConfig();
    }

    protected function getStagingDatabase() {
        $dbStaging = Zend_Db::factory($this->getStagingConfig()->database);
        return $dbStaging;
    }

    protected function getLiveConfig () {
        $config = new Zend_Config_Xml(str_replace(PIMCORE_DOCUMENT_ROOT_STAGE, PIMCORE_DOCUMENT_ROOT_LIVE, PIMCORE_CONFIGURATION_SYSTEM));
        return $config;
    }

    protected function getLiveDatabase() {
        $dbStaging = Zend_Db::factory($this->getLiveConfig()->database);
        return $dbStaging;
    }

    public function init () {

        $this->id = time();
        $errors = array();


        // get steps
        $steps = array();

        // get available tables and views in STAGING database
        $db = $this->getStagingDatabase();
        $tables = $db->fetchAll("SHOW FULL TABLES");

        // tables
        $stepsTable = array();
        foreach ($tables as $table) {

            $name = current($table);
            $type = next($table);

            if ($type != "VIEW") {
                $c = array(
                    "url" => "/admin/staging/disable-mysql",
                    "params" => array(
                        "name" => $name,
                        "type" => $type
                    )
                );

                $this->dbTablesStage[] = $name;
                $stepsTable[] = $c;
            }
        }
        $steps[] = $stepsTable;

        // views
        //$stepsViews = array();
        foreach ($tables as $table) {

            reset($table);
            $name = current($table);
            $type = next($table);

            if ($type == "VIEW") {
                /*$c = array(
                    "url" => "/admin/staging/disable-mysql",
                    "params" => array(
                        "name" => $name,
                        "type" => $type
                    )
                );*/

                $this->dbViewsStage[] = $name;
                //$stepsViews[] = $c;
            }
        }
        //$steps[] = $stepsViews;





        // get available tables and views in LIVE database
        $db = $this->getLiveDatabase();
        $tables = $db->fetchAll("SHOW FULL TABLES");

        // tables
        foreach ($tables as $table) {

            $name = current($table);
            $type = next($table);

            if ($type != "VIEW") {
                $this->dbTablesLive[] = $name;
            }
        }

        // views
        foreach ($tables as $table) {

            reset($table);
            $name = current($table);
            $type = next($table);

            if ($type == "VIEW") {
                $this->dbViewsLive[] = $name;
            }
        }



        $steps[] = array(array(
            "url" => "/admin/staging/disable-complete",
        ));
        $steps[] = array(array(
            "url" => "/admin/settings/clear-cache",
        ));


        if (!empty($errors)) {
            $steps = null;
        }

        return array(
            "steps" => $steps,
            "errors" => $errors
        );
    }

    public function mysql ($name, $type) {
        $dbLive = $this->getLiveDatabase();
        $dbStage = $this->getStagingDatabase();

        $tablePrefix = "STAGE__" . $this->id . "__";

        if ($type != "VIEW") {
            try {
                $dbLive->exec("CREATE TABLE  `" . $tablePrefix. $name . "` LIKE `" . $this->getStagingConfig()->database->params->dbname . "`.`" . $name . "`;");
                $dbLive->exec("INSERT INTO `" . $tablePrefix . $name . "` SELECT * FROM `" . $this->getStagingConfig()->database->params->dbname . "`.`" . $name . "`;");
            } catch (Exception $e) {
                if ($name == "users") {
                    // this is because ID = 0 causes a problem (the system user)
                    $dbLive->exec("INSERT INTO `" . $tablePrefix . $name . "` SELECT * FROM `" . $this->getStagingConfig()->database->params->dbname . "`.`" . $name . "` WHERE id > 0;");
                    // insert system user
                    $dbLive->insert($tablePrefix . $name, array(
                        "id" => 0,
                        "parentId" => 0,
                        "username" => "system",
                        "admin" => 1,
                        "hasCredentials" => 1,
                        "active" => 1
                    ));
                } else {
                    throw $e;
                }
            }
        }

        return array(
            "success" => true
        );
    }

    public function complete () {

        // write new system.xml for live system and keep the database settings from the live system
        $stagingSystemConfig = $this->getStagingConfig()->toArray();
        $liveSystemConfig = $this->getLiveConfig()->toArray();
        $stagingSystemConfig["database"] = $liveSystemConfig["database"];

        // convert all special characters to their entities so the xml writer can put it into the file
        $stagingSystemConfig = array_htmlspecialchars($stagingSystemConfig);

        $stagingSystemConfig = new Zend_Config($stagingSystemConfig, true);
        $writer = new Zend_Config_Writer_Xml(array(
            "config" => $stagingSystemConfig,
            "filename" => str_replace(PIMCORE_DOCUMENT_ROOT_STAGE, PIMCORE_DOCUMENT_ROOT_LIVE, PIMCORE_CONFIGURATION_SYSTEM)
        ));


        // move files

         $dirsToMove = array(
            "pimcore",
            PIMCORE_FRONTEND_MODULE,
            "plugins"
        );

        // create tmp directory where we move the live folders to
        $backupDir = PIMCORE_DOCUMENT_ROOT_LIVE . "/STAGE__" . $this->id;
        mkdir($backupDir);

        // now move the staging folders to the live and the live ones to the backup folder
        foreach ($dirsToMove as $dir) {
            rename(PIMCORE_DOCUMENT_ROOT_LIVE . "/" . $dir, $backupDir . "/" . $dir);
            rename(PIMCORE_DOCUMENT_ROOT_STAGE . "/" . $dir, PIMCORE_DOCUMENT_ROOT_LIVE . "/" . $dir);
        }

        // write new system configuration
        $writer->write();


        // rename the mysql tables and views
        $dbLive = $this->getLiveDatabase();
        $dbStage = $this->getStagingDatabase();
        $tablePrefixTmp = "STAGE__" . $this->id . "__";
        $tablePrefixBackup = "STAGE__BACKUP__" . $this->id . "__";

        foreach ($this->dbViewsLive as $view) {
            $dbLive->exec("RENAME TABLE `" . $view . "` TO `" . $tablePrefixBackup . $view . "`;");
        }
        foreach ($this->dbTablesLive as $table) {
            $dbLive->exec("RENAME TABLE `" . $table . "` TO `" . $tablePrefixBackup . $table . "`;");
        }

        foreach ($this->dbTablesStage as $table) {
            $dbLive->exec("RENAME TABLE `" . $tablePrefixTmp . $table . "` TO `" . $table . "`;");
        }
        foreach ($this->dbViewsLive as $view) {
            $viewCode = $dbStage->fetchRow("show create view `".$view."`;");
            $dbLive->exec($viewCode["Create View"]);
        }



        // remove staging config
        unlink(PIMCORE_CONFIGURATION_STAGE);

        return array(
            "success" => true
        );
    }
}

