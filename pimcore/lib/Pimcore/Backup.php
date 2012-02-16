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

class Pimcore_Backup {

    public $additionalExcludePatterns = array();

    public $filesToBackup;
    public $fileAmount;
    public $backupFile;
    
    public function __construct ($backupFile) {
        $this->backupFile = $backupFile;
    }
    
    public function getFilesToBackup () {
        return $this->filesToBackup;
    }
    
    protected function setFilesToBackup ($files) {
        $this->filesToBackup = $files;
    }
    
    public function getFileAmount () {
        return $this->fileAmount;
    }
    
    protected function setFileAmount ($fileAmount) {
        $this->fileAmount = $fileAmount;
    }
    
    public function getBackupFile () {
        return $this->backupFile;
    }

    public function getAdditionalExcludeFiles () {
        return $this->additionalExcludePatterns;
    }

    public function setAdditionalExcludePatterns ($additionalExcludePatterns) {
        $this->additionalExcludePatterns = $additionalExcludePatterns;
    }

    protected function getFormattedFilesize () {
        return formatBytes(filesize($this->getBackupFile()));
    }
    
    protected function getArchive () {
        $obj = new Archive_Tar($this->getBackupFile());

        if (!is_file($this->getBackupFile())) {

            $files = array();

            if (!$obj->create($files)) {
                echo "can't create archive";
            }
        }

        return $obj;
    }
    
    public function init () {

        // create backup directory if not exists
        if (!is_dir(PIMCORE_BACKUP_DIRECTORY)) {
            if (!mkdir(PIMCORE_BACKUP_DIRECTORY)) {
                Logger::err("Directory " . PIMCORE_BACKUP_DIRECTORY . " does not exists and cannot be created.");
                exit;
            }
        }

        $errors = array();
        $this->setFileAmount(0);
        

        // cleanup old backups
        if (is_file(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql")) {
            unlink(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql");
        }

        // get steps
        $steps = array();

        // get available tables
        $db = Pimcore_Resource::get();
        $tables = $db->fetchAll("SHOW FULL TABLES");


        $steps[] = array("mysql-tables", null);

        // tables
        foreach ($tables as $table) {

            $name = current($table);
            $type = next($table);

            if ($type != "VIEW") {
                $steps[] = array("mysql", array(
                    "name" => $name,
                    "type" => $type
                ));
            }
        }

        // views
        foreach ($tables as $table) {

            reset($table);
            $name = current($table);
            $type = next($table);

            if ($type == "VIEW") {
                $steps[] = array("mysql", array(
                    "name" => $name,
                    "type" => $type
                ));
            }
        }


        $steps[] = array("mysql-complete", null);

        // check files
        $currentFileCount = 0;
        $currentFileSize = 0;
        $currentStepFiles = array();


        // check permissions
        $filesIn = rscandir(PIMCORE_DOCUMENT_ROOT . "/");
        clearstatcache();

        foreach ($filesIn as $fileIn) {
            if (!is_readable($fileIn)) {
                $errors[] = $fileIn . " is not readable.";
            }

            if ($currentFileCount > 300 || $currentFileSize > 20000000) {

                $currentFileCount = 0;
                $currentFileSize = 0;
                if (!empty($currentStepFiles)) {
                    $filesToBackup[] = $currentStepFiles;
                }
                $currentStepFiles = array();
            }

            if(file_exists($fileIn)) {
                $currentFileSize += filesize($fileIn);
                $currentFileCount++;
                $currentStepFiles[] = $fileIn;
            }
        }

        if (!empty($currentStepFiles)) {
            $filesToBackup[] = $currentStepFiles;
        }

        $this->setFilesToBackup($filesToBackup);

        $fileSteps = count($filesToBackup);

        for ($i = 0; $i < $fileSteps; $i++) {
            $steps[] = array("files", array(
                "step" => $i
            ));
        }

        $steps[] = array("complete", null);


        if (!empty($errors)) {
            $steps = null;
        }

        return array(
            "steps" => $steps,
            "errors" => $errors
        );
    }
    
    public function fileStep ($step) {
        
        $filesContainer = $this->getFilesToBackup();
        $files = $filesContainer[$step];

        $excludePatterns = array(
            "/" . PIMCORE_FRONTEND_MODULE . "\/var\/backup\/.*/",
            "/" . PIMCORE_FRONTEND_MODULE . "\/var\/cache\/.*/",
            "/" . PIMCORE_FRONTEND_MODULE . "\/var\/log\/.*/",
            "/" . PIMCORE_FRONTEND_MODULE . "\/var\/system\/.*/",
            "/" . PIMCORE_FRONTEND_MODULE . "\/var\/tmp\/.*/",
            "/" . PIMCORE_FRONTEND_MODULE . "\/var\/webdav\/.*/"
        );

        if(!empty($this->additionalExcludePatterns) && is_array($this->additionalExcludePatterns)) {
            $excludePatterns = array_merge($excludePatterns, $this->additionalExcludePatterns);
        }

        clearstatcache();

        foreach ($files as $file) {
            if ($file) {
                if (file_exists($file) && is_readable($file)) {

                    $exclude = false;
                    $relPath = str_replace(PIMCORE_DOCUMENT_ROOT . "/", "", $file);

                    foreach ($excludePatterns as $pattern) {
                        if (preg_match($pattern, $relPath)) {
                            $exclude = true;
                        }
                    }

                    if (!$exclude && is_file($file)) {
                        $this->getArchive()->addString($relPath, file_get_contents($file));
                    }
                    else {
                        Logger::info("Backup: Excluded: " . $file);
                    }
                }
                else {
                    Logger::err("Backup: Can't read file: " . $file);
                }
            }
        }

        $this->setFileAmount($this->getFileAmount()+count($files));

        return array(
            "success" => true,
            "filesize" => $this->getFormattedFilesize(),
            "fileAmount" => $this->getFileAmount()
        );
    }
    
    public function mysqlTables () {
        $db = Pimcore_Resource::get();

        $tables = $db->fetchAll("SHOW FULL TABLES");

        $dumpData = "\nSET NAMES UTF8;\n\n";

        // tables
        foreach ($tables as $table) {

            $name = current($table);
            $type = next($table);

            if ($type != "VIEW") {
                $dumpData .= "\n\n";
                $dumpData .= "DROP TABLE IF EXISTS `" . $name . "`;";
                $dumpData .= "\n";

                $tableData = $db->fetchRow("SHOW CREATE TABLE " . $name);

                $dumpData .= $tableData["Create Table"] . ";";

                $dumpData .= "\n\n";
            }

        }

        $dumpData .= "\n\n";


        $h = fopen(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql", "a+");
        fwrite($h, $dumpData);
        fclose($h);

        return array(
            "success" => true
        );
    }
    
    public function mysqlData ($name, $type) {
        $db = Pimcore_Resource::reset();

        $dumpData = "\n\n";

        if ($type != "VIEW") {
            // backup tables
            $tableData = $db->fetchAll("SELECT * FROM " . $name);

            foreach ($tableData as $row) {

                $cells = array();
                foreach ($row as $cell) {
                    $cells[] = $db->quote($cell);
                }

                $dumpData .= "INSERT INTO `" . $name . "` VALUES (" . implode(",", $cells) . ");";
                $dumpData .= "\n";

            }
        }
        else {
            // dump view structure
            $dumpData .= "\n\n";
            $dumpData .= "DROP VIEW IF EXISTS `" . $name . "`;";
            $dumpData .= "\n";

            try {
                $viewData = $db->fetchRow("SHOW CREATE VIEW " . $name);
                $dumpData .= $viewData["Create View"] . ";";
            } catch (Exception $e) {
                Logger::error($e);
            }
        }

        $dumpData .= "\n\n";

        $h = fopen(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql", "a+");
        fwrite($h, $dumpData);
        fclose($h);

        return array(
            "success" => true
        );
    }
    
    public function mysqlComplete() {
        $this->getArchive()->addString("dump.sql", file_get_contents(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql"));

        // cleanup
        unlink(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql");

        return array(
            "success" => true,
            "filesize" => $this->getFormattedFilesize()
        );
    }
    
    public function complete () {
        $this->getArchive()->addString(PIMCORE_FRONTEND_MODULE . "/var/cache/.dummy", "dummy");
        $this->getArchive()->addString(PIMCORE_FRONTEND_MODULE . "/var/tmp/.dummy", "dummy");
        $this->getArchive()->addString(PIMCORE_FRONTEND_MODULE . "/var/log/debug.log", "dummy");
        $this->getArchive()->addString("index.php", file_get_contents(PIMCORE_DOCUMENT_ROOT . "/index.php"));
        $this->getArchive()->addString(".htaccess", file_get_contents(PIMCORE_DOCUMENT_ROOT . "/.htaccess"));


        return array(
            "success" => true,
            "download" => str_replace(PIMCORE_DOCUMENT_ROOT, "", $this->getBackupFile()),
            "filesystem" => $this->getBackupFile()
        );
    }
}

