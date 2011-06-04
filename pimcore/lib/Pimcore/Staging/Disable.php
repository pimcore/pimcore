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
    
    public $filesToStage;
    public $fileAmount;
    
    
    public function getFilesToStage () {
        return $this->filesToStage;
    }
    
    protected function setFilesToStage ($files) {
        $this->filesToStage = $files;
    }
    
    public function getFileAmount () {
        return $this->fileAmount;
    }
    
    protected function setFileAmount ($fileAmount) {
        $this->fileAmount = $fileAmount;
    }

    protected function getStagingDatabase() {

        $dbStaging = Zend_Db::factory(Pimcore_Config::getSystemConfig()->staging->database);
        return $dbStaging;
    }

    protected function getLiveDatabase() {

        $dbStaging = Zend_Db::factory(Pimcore_Config::getSystemConfig()->staging->database);
        return $dbStaging;
    }

    public function cleanupDatabase () {
        // cleanup staging database
        $dbStaging = $this->getStagingDatabase();
        $tablesStage = $dbStaging->fetchAll("SHOW FULL TABLES");

        // views
        foreach ($tablesStage as $table) {
            $name = current($table);
            $type = next($table);

            if ($type == "VIEW") {
                $dbStaging->exec("DROP VIEW `" . $name . "`;");
            }
        }

        // tables
        foreach ($tablesStage as $table) {
            $name = current($table);
            $type = next($table);

            if ($type != "VIEW") {
                $dbStaging->exec("DROP TABLE `" . $name . "`;");
            }
        }

        return array(
            "success" => true
        );
    }

    public function cleanupFiles () {
        // cleanup old staging files
        recursiveDelete(PIMCORE_DOCUMENT_ROOT_STAGE,false);

        return array(
            "success" => true
        );
    }

    public function setUpStaging () {

        // create website/var folders
        $dirContent = scandir(PIMCORE_WEBSITE_PATH . "/var");
        foreach ($dirContent as $content) {
            if(is_dir(PIMCORE_WEBSITE_PATH . "/var/" . $content)) {

                if(strpos($content,".") !== false) {
                    continue;
                }
                mkdir(str_replace(PIMCORE_DOCUMENT_ROOT, PIMCORE_DOCUMENT_ROOT_STAGE, PIMCORE_WEBSITE_PATH . "/var")."/".$content,0766,true);
            }
        }

        return array(
            "success" => true
        );
    }
        
    public function init () {
        // create staging directory if not exists
        if (!is_dir(PIMCORE_DOCUMENT_ROOT_STAGE)) {
            if (!mkdir(PIMCORE_DOCUMENT_ROOT_STAGE)) {
                logger::err("Staging - Directory " . PIMCORE_DOCUMENT_ROOT_STAGE . " does not exists and cannot be created.");
                exit;
            }
        }

        // check if the staging directory is writeable
        if(!is_writeable(PIMCORE_DOCUMENT_ROOT_STAGE)) {
            logger::err("Staging - Directory " . PIMCORE_DOCUMENT_ROOT_STAGE . " does is not writeable.");
            exit;
        }

        // config 
        $dirsToStage = array(
            "pimcore",
            PIMCORE_FRONTEND_MODULE,
            "plugins"
        );

        $errors = array();
        $this->setFileAmount(0);



        // get steps
        $steps = array();


        $steps[] = array(array(
            "url" => "/admin/staging/enable-cleanup-database",
        ));

        $steps[] = array(array(
            "url" => "/admin/staging/enable-cleanup-files",
        ));

        $steps[] = array(array(
            "url" => "/admin/staging/enable-setup",
        ));

        // get available tables
        $db = Pimcore_Resource::get();
        $tables = $db->fetchAll("SHOW FULL TABLES");

        // tables
        $stepsTable = array();
        foreach ($tables as $table) {

            $name = current($table);
            $type = next($table);

            if ($type != "VIEW") {
                $stepsTable[] = array(
                    "url" => "/admin/staging/enable-mysql",
                    "params" => array(
                        "name" => $name,
                        "type" => $type
                    )
                );
            }
        }
        $steps[] = $stepsTable;

        // views
        $stepsViews = array();
        foreach ($tables as $table) {

            reset($table);
            $name = current($table);
            $type = next($table);

            if ($type == "VIEW") {
                $stepsViews[] = array(
                    "url" => "/admin/staging/enable-mysql",
                    "params" => array(
                        "name" => $name,
                        "type" => $type
                    )
                );
            }
        }
        $steps[] = $stepsViews;

        // check files
        $stepsFiles = array();
        $currentFileCount = 0;
        $currentFileSize = 0;
        $currentStepFiles = array();

        $files = scandir(PIMCORE_DOCUMENT_ROOT);
        foreach ($files as $file) {
            $dir = PIMCORE_DOCUMENT_ROOT . "/" . $file;
            if (is_dir($dir) && in_array($file, $dirsToStage)) {
                // check permissions
                $filesIn = rscandir($dir . "/");

                foreach ($filesIn as $fileIn) {
                    if (!is_readable($fileIn)) {
                        $errors[] = $fileIn . " is not readable.";
                    }

                    if ($currentFileCount > 300 || $currentFileSize > 20000000) {

                        $currentFileCount = 0;
                        $currentFileSize = 0;
                        if (!empty($currentStepFiles)) {
                            $filesToStage[] = $currentStepFiles;
                        }
                        $currentStepFiles = array();
                    }

                    $currentFileSize += filesize($fileIn);
                    $currentFileCount++;
                    $currentStepFiles[] = $fileIn;
                }

                $currentFileCount = 0;
                $currentFileSize = 0;
                if (!empty($currentStepFiles)) {
                    $filesToStage[] = $currentStepFiles;
                }

                $currentStepFiles = array();
            }
        }

        $this->setFilesToStage($filesToStage);

        $fileSteps = count($filesToStage);

        for ($i = 0; $i < $fileSteps; $i++) {
            $stepsFiles[] = array(
                "url" => "/admin/staging/enable-files",
                "params" => array(
                    "step" => $i
                )
            );
        }
        $steps[] = $stepsFiles;

        $steps[] = array(array(
            "url" => "/admin/staging/enable-complete",
        ));


        if (!empty($errors)) {
            $steps = null;
        }

        return array(
            "steps" => $steps,
            "errors" => $errors
        );
    }
    
    public function fileStep ($step) {
        
        $filesContainer = $this->getFilesToStage();
        $files = $filesContainer[$step];

        $excludePatterns = array(
            "/^" . PIMCORE_FRONTEND_MODULE . "\/var\/backup\/.*/",
            "/^" . PIMCORE_FRONTEND_MODULE . "\/var\/cache\/.*/",
            "/^" . PIMCORE_FRONTEND_MODULE . "\/var\/log\/.*/",
            "/^" . PIMCORE_FRONTEND_MODULE . "\/var\/system\/.*/",
            "/^" . PIMCORE_FRONTEND_MODULE . "\/var\/tmp\/.*/",
            "/^" . PIMCORE_FRONTEND_MODULE . "\/var\/webdav\/.*/"
        );

        foreach ($files as $file) {
            if ($file) {
                if (is_readable($file)) {

                    $exclude = false;
                    $relPath = str_replace(PIMCORE_DOCUMENT_ROOT . "/", "", $file);

                    foreach ($excludePatterns as $pattern) {
                        if (preg_match($pattern, str_replace("\\", "/", $relPath) )) {
                            $exclude = true;
                        }
                    }

                    if (!$exclude && is_file($file) && !is_dir($file)) {
                        $destFile = PIMCORE_DOCUMENT_ROOT_STAGE . "/" . $relPath;
                        $destDir = dirname($destFile);

                        if(!is_dir($destDir)) {
                            mkdir($destDir,0766,true);
                        }
                        copy($file,$destFile);
                    }
                    else {
                        logger::info("Staging: Excluded: " . $file);
                    }
                }
                else {
                    logger::err("Staging: Can't read file: " . $file);
                }
            }
        }

        $this->setFileAmount($this->getFileAmount()+count($files));

        return array(
            "success" => true,
            "fileAmount" => $this->getFileAmount()
        );
    }

    
    public function mysql ($name, $type) {
        $dbLive = Pimcore_Resource::get();
        $dbStage = $this->getStagingDatabase();


        if ($type != "VIEW") {

            try {
                $dbStage->exec("CREATE TABLE  `" . $name . "` LIKE `" . Pimcore_Config::getSystemConfig()->database->params->dbname . "`.`" . $name . "`;");
                $dbStage->exec("INSERT INTO `" . $name . "` SELECT * FROM `" . Pimcore_Config::getSystemConfig()->database->params->dbname . "`.`" . $name . "`;");
            } catch (Exception $e) {
                if ($name == "users") {
                    $dbStage->exec("INSERT INTO `" . $name . "` SELECT * FROM `" . Pimcore_Config::getSystemConfig()->database->params->dbname . "`.`" . $name . "` WHERE id > 0;");
                } else {
                    throw $e;
                }
            }
        }
        else {
            $viewCode = $dbLive->fetchRow("show create view `".$name."`;");
            $dbStage->exec($viewCode["Create View"]);
        }

        return array(
            "success" => true
        );
    }

    public function complete () {

        $systemConfig = Pimcore_Config::getSystemConfig();

        // write new system.xml for staging
        $stagingSystemConfig = $systemConfig->toArray();
        $stagingSystemConfig["database"] = $stagingSystemConfig["staging"]["database"];

        // convert all special characters to their entities so the xml writer can put it into the file
        $stagingSystemConfig = array_htmlspecialchars($stagingSystemConfig);

        $stagingSystemConfig = new Zend_Config($stagingSystemConfig, true);
        $writer = new Zend_Config_Writer_Xml(array(
            "config" => $stagingSystemConfig,
            "filename" => str_replace(PIMCORE_DOCUMENT_ROOT, PIMCORE_DOCUMENT_ROOT_STAGE, PIMCORE_CONFIGURATION_SYSTEM)
        ));
        $writer->write();

        
        // write staging config
        $stagingConfig = array(
            "stagingdomain" => $systemConfig->staging->domain,
            "livedomain" => $_SERVER["HTTP_HOST"]
        );

        $stagingConfig = new Zend_Config($stagingConfig);
        $writer = new Zend_Config_Writer_Ini(array(
            "config" => $stagingConfig,
            "filename" => PIMCORE_CONFIGURATION_STAGE
        ));
        $writer->write();

        return array(
            "success" => true
        );
    }
}

