<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

class Backup
{

    /**
     * @var array
     */
    public $additionalExcludePatterns = [];

    /**
     * @var
     */
    public $filesToBackup;

    /**
     * @var
     */
    public $fileAmount;

    /**
     * @var
     */
    public $backupFile;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var \ZipArchive
     */
    protected $zipArchive;

    /**
     * @param $backupFile
     */
    public function __construct($backupFile)
    {
        $this->backupFile = $backupFile;
    }

    /**
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return mixed
     */
    public function getFilesToBackup()
    {
        return $this->filesToBackup;
    }

    /**
     * @param $files
     * @return $this
     */
    protected function setFilesToBackup($files)
    {
        $this->filesToBackup = $files;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFileAmount()
    {
        return $this->fileAmount;
    }

    /**
     * @param $fileAmount
     * @return $this
     */
    protected function setFileAmount($fileAmount)
    {
        $this->fileAmount = $fileAmount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBackupFile()
    {
        return $this->backupFile;
    }

    /**
     * @return array
     */
    public function getAdditionalExcludeFiles()
    {
        return $this->additionalExcludePatterns;
    }

    /**
     * @param $additionalExcludePatterns
     * @return $this
     */
    public function setAdditionalExcludePatterns($additionalExcludePatterns)
    {
        $this->additionalExcludePatterns = $additionalExcludePatterns;

        return $this;
    }

    /**
     * @return string
     */
    protected function getFormattedFilesize()
    {
        if ($this->zipArchive) {
            $this->zipArchive->close();
            $this->zipArchive = null;
        }

        return @formatBytes(filesize($this->getBackupFile()));
    }

    /**
     * @throws \Exception
     */
    protected function getArchive()
    {

        // if already initialized, just return the handler
        if ($this->zipArchive) {
            return $this->zipArchive;
        }

        $this->zipArchive = new \ZipArchive();
        if (!is_file($this->getBackupFile())) {
            $zipState = $this->zipArchive->open($this->getBackupFile(), \ZipArchive::CREATE);
        } else {
            $zipState = $this->zipArchive->open($this->getBackupFile());
        }

        if ($zipState === true) {
            return $this->zipArchive;
        } else {
            throw new \Exception("unable to create zip archive");
        }
    }

    /**
     * @param array $options
     * @return array
     */
    public function init($options = [])
    {
        $this->setOptions($options);

        // create backup directory if not exists
        if (!is_dir(PIMCORE_BACKUP_DIRECTORY)) {
            if (!\Pimcore\File::mkdir(PIMCORE_BACKUP_DIRECTORY)) {
                Logger::err("Directory " . PIMCORE_BACKUP_DIRECTORY . " does not exists and cannot be created.");
                exit;
            }
        }

        $errors = [];
        $this->setFileAmount(0);


        // cleanup old backups
        if (is_file(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql")) {
            unlink(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql");
        }

        // get steps
        $steps = [];

        // get available tables
        $db = Db::get();
        $tables = $this->getTables();


        $steps[] = ["mysql-tables", $this->options['mysql-tables']];

        // tables
        foreach ($tables as $table) {
            $name = current($table);
            $type = next($table);

            if ($type != "VIEW") {
                $steps[] = ["mysql", [
                    "name" => $name,
                    "type" => $type
                ]];
            }
        }

        // views
        foreach ($tables as $table) {
            reset($table);
            $name = current($table);
            $type = next($table);

            if ($type == "VIEW") {
                $steps[] = ["mysql", [
                    "name" => $name,
                    "type" => $type
                ]];
            }
        }


        $steps[] = ["mysql-complete", null];

        if (!$options['only-mysql-related-tasks']) {
            // check files
            $currentFileCount = 0;
            $currentFileSize = 0;
            $currentStepFiles = [];


            // check permissions
            $filesIn = rscandir(PIMCORE_PROJECT_ROOT . "/");
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
                    $currentStepFiles = [];
                }

                if (file_exists($fileIn)) {
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
                $steps[] = ["files", [
                    "step" => $i
                ]];
            }

            $steps[] = ["complete", null];
        }

        if (!empty($errors)) {
            $steps = null;
        }

        return [
            "steps" => $steps,
            "errors" => $errors
        ];
    }

    /**
     * @param $step
     * @return array
     * @throws \Exception
     */
    public function fileStep($step)
    {
        $filesContainer = $this->getFilesToBackup();
        $files = $filesContainer[$step];

        $excludePatterns = [
            "/.editorconfig.*",
            "/.git.*",
            "/.travis.*",
            "/var/assets/tmp/.*",
            "/web/var/assets/tmp/.*",
             "/var/backup/.*",
            "/var/cache/.*",
            "/var/log/.*",
            "/var/system/.*",
            "/var/tmp/.*",
            "/var/webdav/.*"
        ];

        //TODO composer ?

        if (!empty($this->additionalExcludePatterns) && is_array($this->additionalExcludePatterns)) {
            $excludePatterns = array_merge($excludePatterns, $this->additionalExcludePatterns);
        }

        foreach ($excludePatterns as &$excludePattern) {
            $excludePattern = "@" . $excludePattern . "@";
        }

        clearstatcache();

        foreach ($files as $file) {
            if ($file) {
                if (file_exists($file) && is_readable($file)) {
                    $exclude = false;
                    $relPath = str_replace(PIMCORE_PROJECT_ROOT, "", $file);
                    $relPath = str_replace(DIRECTORY_SEPARATOR, "/", $relPath); // windows compatibility

                    foreach ($excludePatterns as $pattern) {
                        if (preg_match($pattern, $relPath)) {
                            $exclude = true;
                        }
                    }

                    if (!$exclude && is_file($file)) {
                        $this->getArchive()->addFile($file, ltrim($relPath, "/"));
                    } else {
                        Logger::info("Backup: Excluded: " . $file);
                    }
                } else {
                    Logger::err("Backup: Can't read file: " . $file);
                }
            }
        }

        $this->setFileAmount($this->getFileAmount()+count($files));

        return [
            "success" => true,
            "filesize" => $this->getFormattedFilesize(),
            "fileAmount" => $this->getFileAmount()
        ];
    }

    /**
     * @return array
     */
    protected function getTables()
    {
        $db = Db::get();

        if ($mysqlTables = $this->options['mysql-tables']) {
            $specificTables = explode(',', $mysqlTables);
            $databaseName = (string) \Pimcore\Config::getSystemConfig()->database->params->dbname;
            $query = "SHOW FULL TABLES where `Tables_in_". $databaseName . "` IN(" . implode(',', wrapArrayElements($specificTables)) . ')';
        } else {
            $query = "SHOW FULL TABLES";
        }

        $tables = $db->fetchAll($query);

        return $tables;
    }

    /**
     * @param array $exclude
     * @return array
     */
    public function mysqlTables($exclude = [])
    {
        $db = Db::get();

        $tables = $this->getTables();

        $dumpData = "\nSET NAMES utf8mb4;\n\n";

        // tables
        foreach ($tables as $table) {
            $name = current($table);
            $type = next($table);

            if (in_array($name, $exclude)) {
                continue;
            }

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


        $h = fopen(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql", "a+", false, File::getContext());
        fwrite($h, $dumpData);
        fclose($h);

        return [
            "success" => true
        ];
    }

    /**
     * @param $name
     * @param $type
     * @return array
     */
    public function mysqlData($name, $type)
    {
        $db = Db::reset();

        $dumpData = "\n\n";

        $name = $db->quoteTableAs($name);

        if ($type != "VIEW") {
            // backup tables
            $tableData = $db->fetchAll("SELECT * FROM " . $name);

            foreach ($tableData as $row) {
                $cells = [];
                foreach ($row as $cell) {
                    if (is_string($cell)) {
                        $cell = $db->quote($cell);
                    } elseif ($cell === null) {
                        $cell = "NULL";
                    }

                    $cells[] = $cell;
                }

                $dumpData .= "INSERT INTO " . $name . " VALUES (" . implode(",", $cells) . ");";
                $dumpData .= "\n";
            }
        } else {
            // dump view structure
            $dumpData .= "\n\n";
            $dumpData .= "DROP VIEW IF EXISTS " . $name . ";";
            $dumpData .= "\n";

            try {
                $viewData = $db->fetchRow("SHOW CREATE VIEW " . $name);
                $dumpData .= $viewData["Create View"] . ";";
            } catch (\Exception $e) {
                Logger::error($e);
            }
        }

        $dumpData .= "\n\n";

        $h = fopen(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql", "a+", false, File::getContext());
        fwrite($h, $dumpData);
        fclose($h);

        return [
            "success" => true
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function mysqlComplete()
    {
        $this->getArchive()->addFile(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql", "dump.sql");
        // cleanup
        //unlink(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/backup-dump.sql");

        return [
            "success" => true,
            "filesize" => $this->getFormattedFilesize()
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function complete()
    {
        $this->getArchive()->addFromString("var/cache/.dummy", "dummy");
        $this->getArchive()->addFromString("var/tmp/.dummy", "dummy");
        $this->getArchive()->addFromString("var/backup/.dummy", "dummy");
        $this->getArchive()->addFromString("var/log/.dummy", "dummy");
        $this->getArchive()->addFromString("var/system/.dummy", "dummy");
        $this->getArchive()->addFromString("var/webdav/.dummy", "dummy");
        $this->getArchive()->addFromString("var/log/dev.log", "dummy");
        $this->getArchive()->addFromString("var/log/prod.log", "dummy");

        $this->getArchive()->addFile(PIMCORE_PROJECT_ROOT . "/index.php", "index.php");
        $this->getArchive()->addFile(PIMCORE_PROJECT_ROOT . "/.htaccess", ".htaccess");

        return [
            "success" => true,
            "download" => str_replace(PIMCORE_PROJECT_ROOT, "", $this->getBackupFile()),
            "filesystem" => $this->getBackupFile()
        ];
    }

    /**
     *
     */
    public function __wakeup()
    {
        $this->zipArchive = null;
    }

    /**
     *
     */
    public function __destruct()
    {
        if ($this->zipArchive) {
            @$this->zipArchive->close();
        }
    }
}
