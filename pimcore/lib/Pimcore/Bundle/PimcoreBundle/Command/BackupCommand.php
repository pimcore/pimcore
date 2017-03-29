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

namespace Pimcore\Bundle\PimcoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class BackupCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('backup')
            ->setDescription('Creates .zip archive of document root')
            ->addOption(
                'filename', 'f',
                InputOption::VALUE_OPTIONAL,
                "filename for the backup (default: backup_m-d-Y_H-i) .zip is added automatically"
            )
            ->addOption(
                'directory', 'd',
                InputOption::VALUE_OPTIONAL,
                'target directory (absolute path without trailing slash) for the backup-file (default: ' . PIMCORE_BACKUP_DIRECTORY . ')'
            )
            ->addOption(
                'overwrite', 'o',
                InputOption::VALUE_NONE,
                'overwrite existing backup with the same filename, default: true'
            )
            ->addOption(
                'cleanup', 'c',
                InputOption::VALUE_OPTIONAL,
                'in days, backups in the target directory which are older than the given days will be deleted, default 7, use false to disable it'
            )
            ->addOption(
                'mysql-tables', null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'a comma separated list of mysql tables to backup e.g "translations_website,translations_admin" '
            )
            ->addOption(
                'only-mysql-related-tasks', null,
                InputOption::VALUE_NONE,
                'executes only mysql related tasks.'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // defaults
        $config = [
            "filename" => "backup_" . date("m-d-Y_H-i"),
            "directory" => PIMCORE_BACKUP_DIRECTORY,
            "overwrite" => false,
            "cleanup" => 7,
        ];

        $tmpConfig = $config;
        foreach ($config as $key => $value) {
            if ($input->getOption($key)) {
                $tmpConfig[$key] = $input->getOption($key);
            }
        }
        $config = $tmpConfig;
        \Pimcore\Cache\Runtime::set("config", $config);

        $backupFile = $config["directory"] . "/" . $config["filename"] . ".zip";


        // check for existing file
        if (is_file($backupFile) && !$config["overwrite"]) {
            $this->writeError("backup-file already exists, please use --overwrite=true or -o true to overwrite it");
            exit;
        } elseif (is_file($backupFile)) {
            @unlink($backupFile);
        }

        // cleanup
        if ($config["cleanup"] != "false") {
            $files = scandir($config["directory"]);
            $lifetime = (int) $config["cleanup"] * 86400;
            foreach ($files as $file) {
                if (is_file($config["directory"] . "/" . $file) && preg_match("/\.zip$/", $file)) {
                    if (filemtime($config["directory"] . "/" . $file) < (time() - $lifetime)) {
                        $this->verboseMessage("delete: " . $config["directory"] . "/" . $file . "\n");
                        unlink($config["directory"] . "/" . $file);
                    }
                }
            }
        }

        $this->verboseMessage("------------------------------------------------");
        $this->verboseMessage("------------------------------------------------");
        $this->verboseMessage("starting backup into file: " . $backupFile);
        $options = [];
        if ($mysqlTables = $input->getOption("mysql-tables")) {
            $options["mysql-tables"] = $mysqlTables;
        }
        $options['only-mysql-related-tasks'] = $input->getOption('only-mysql-related-tasks');



        $backup = new \Pimcore\Backup($backupFile);
        $initInfo = $backup->init($options);

        $stepMethodMapping = [
            "mysql-tables" => "mysqlTables",
            "mysql" => "mysqlData",
            "mysql-complete" => "mysqlComplete",
            "files" => "fileStep",
            "complete" => "complete"
        ];

        if (empty($initInfo["errors"])) {
            $progress = new ProgressBar($output, count($initInfo["steps"]));
            if (!$output->isVerbose()) {
                $progress->start();
            }

            foreach ($initInfo["steps"] as $step) {
                if (!is_array($step[1])) {
                    $step[1] = [];
                }

                $message = $step[0] . ": " . implode(",", $step[1]);

                $return = call_user_func_array([$backup, $stepMethodMapping[$step[0]]], $step[1]);
                if ($return["filesize"]) {
                    $message .= " - " . $return["filesize"];
                }

                $progress->setMessage($message);
                $progress->advance();
            }

            $progress->finish();
        }

        $this->verboseMessage("------------------------------------------------");
        $this->verboseMessage("------------------------------------------------");
        /*
         * do not remove the string "backup finished"
         * deployment will check for this string to ensure that the backup has been successfully created
         * and no fatal error occurred during backup-creation
         */
        $this->verboseMessage("backup finished, you can find your backup here: " . $backupFile);

        $this->output->writeln("\n");
        $this->output->writeln("Done!");
    }

    /**
     * @param $m
     */
    protected function verboseMessage($m)
    {
        if ($this->output->isVerbose()) {
            $this->output->writeln($m);
        }
    }
}
