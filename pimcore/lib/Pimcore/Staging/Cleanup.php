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

class Pimcore_Staging_Cleanup {
    
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


    public function init () {

        $errors = array();
        $this->setFileAmount(0);

        // get steps
        $steps = array();

        // get available tables
        $db = Pimcore_Resource::get();
        $tables = $db->fetchAll("SHOW FULL TABLES LIKE 'STAGE__%';");

        // tables
        $stepsTable = array();
        foreach ($tables as $table) {

            $name = current($table);
            $type = next($table);

            if ($type != "VIEW") {
                $stepsTable[] = array(
                    "url" => "/admin/staging/cleanup-mysql",
                    "params" => array(
                        "name" => $name,
                        "type" => $type
                    )
                );
            }
        }

        if(!empty($stepsTable)) {
            $steps[] = $stepsTable;
        }

        // views
        $stepsViews = array();
        foreach ($tables as $table) {

            reset($table);
            $name = current($table);
            $type = next($table);

            if ($type == "VIEW") {
                $stepsViews[] = array(
                    "url" => "/admin/staging/cleanup-mysql",
                    "params" => array(
                        "name" => $name,
                        "type" => $type
                    )
                );
            }
        }

        if(!empty($stepsViews)) {
            $steps[] = $stepsViews;
        }



        // check files
        $stepsFiles = array();
        $currentFileCount = 0;
        $currentFileSize = 0;
        $currentStepFiles = array();

        $files = scandir(PIMCORE_DOCUMENT_ROOT);
        foreach ($files as $file) {

            if($file != "pimcore-staging" && !preg_match("/^STAGE__.*/",$file)) {
                continue;
            }

            $dir = PIMCORE_DOCUMENT_ROOT . "/" . $file;
            if (is_dir($dir)) {
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
                "url" => "/admin/staging/cleanup-files",
                "params" => array(
                    "step" => $i
                )
            );
        }
        $steps[] = $stepsFiles;




        $steps[] = array(array(
            "url" => "/admin/staging/cleanup-complete",
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

        foreach ($files as $file) {
            if ($file && is_file($file)) {
                unlink($file);
            }
        }

        $this->setFileAmount($this->getFileAmount()+count($files));

        return array(
            "success" => true,
            "fileAmount" => $this->getFileAmount()
        );
    }

    
    public function mysql ($name, $type) {

        $db = Pimcore_Resource::get();

        if ($type != "VIEW") {
            $db->exec("DROP TABLE `" . $name . "`;");
        }
        else {
            $db->exec("DROP VIEW `" . $name . "`;");
        }

        return array(
            "success" => true
        );
    }

    public function complete () {

        recursiveDelete(PIMCORE_DOCUMENT_ROOT_STAGE,true);


        $files = scandir(PIMCORE_DOCUMENT_ROOT);
        foreach ($files as $file) {

            if($file == "pimcore-staging" || preg_match("/^STAGE__.*/",$file)) {
                $dir = PIMCORE_DOCUMENT_ROOT . "/" . $file;
                recursiveDelete($dir);
            }
        }


        return array(
            "success" => true
        );
    }
}

