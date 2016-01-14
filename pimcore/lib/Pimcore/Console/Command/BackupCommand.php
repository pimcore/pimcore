<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Console\Command;

use Pimcore\Cache\Tool\Warming;
use Pimcore\Console\AbstractCommand;
use Pimcore\Tool\Admin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class BackupCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('backup')
            ->setDescription('Creates .tar archive of document root')
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
                InputOption::VALUE_OPTIONAL,
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
                InputOption::VALUE_OPTIONAL,
                'executes only mysql related tasks.'
            )
            ->addOption(
                'maintenance', 'm',
                InputOption::VALUE_OPTIONAL,
                'set maintenance mode during backup'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->output->writeln($input->getOption("verbose"));


        // defaults
        $config = array(
            "filename" => "backup_" . date("m-d-Y_H-i"),
            "directory" => PIMCORE_BACKUP_DIRECTORY,
            "overwrite" => false,
            "cleanup" => 7,
            "verbose" => false,
            "maintenance" => false
        );


// display help message
        if ($opts->getOption("help")) {
            echo $opts->getUsageMessage();
            exit;
        }

        $tmpConfig = $config;
        foreach ($config as $key => $value) {
            if ($opts->getOption($key)) {
                $tmpConfig[$key] = $opts->getOption($key);
            }
        }
        $config = $tmpConfig;
        \Zend_Registry::set("config", $config);

        $backupFile = $config["directory"] . "/" . $config["filename"] . ".zip";


// check for existing file
        if (is_file($backupFile) && !$config["overwrite"]) {
            echo "backup-file already exists, please use --overwrite=true or -o true to overwrite it";
            exit;
        } else if (is_file($backupFile)) {
            @unlink($backupFile);
        }

// cleanup
        if ($config["cleanup"] != "false") {
            $files = scandir($config["directory"]);
            $lifetime = (int) $config["cleanup"] * 86400;
            foreach ($files as $file) {
                if (is_file($config["directory"] . "/" . $file) && preg_match("/\.zip$/", $file)) {
                    if (filemtime($config["directory"] . "/" . $file) < (time() - $lifetime)) {
                        verboseMessage("delete: " . $config["directory"] . "/" . $file . "\n");
                        unlink($config["directory"] . "/" . $file);
                    }
                }
            }
        }

// maintenance
        if ($config["maintenance"] == true) {
            session_start();
            verboseMessage("------------------------------------------------");
            verboseMessage("set maintenance mode on");
            Pimcore\Tool\Admin::activateMaintenanceMode();
        }

        verboseMessage("------------------------------------------------");
        verboseMessage("------------------------------------------------");
        verboseMessage("starting backup into file: " . $backupFile);
        $options = array();
        if ($mysqlTables = $opts->getOption("mysql-tables")) {
            $options["mysql-tables"] = $mysqlTables;
        }
        $options['only-mysql-related-tasks'] = $opts->getOption('only-mysql-related-tasks');



        $backup = new \Pimcore\Backup($backupFile);
        $initInfo = $backup->init($options);

        $stepMethodMapping = array(
            "mysql-tables" => "mysqlTables",
            "mysql" => "mysqlData",
            "mysql-complete" => "mysqlComplete",
            "files" => "fileStep",
            "complete" => "complete"
        );

        if (empty($initInfo["errors"])) {
            foreach ($initInfo["steps"] as $step) {
                if (!is_array($step[1])) {
                    $step[1] = array();
                }
                verboseMessage("execute: " . $step[0] . " | with the following parameters: " . implode(",", $step[1]));
                $return = call_user_func_array(array($backup, $stepMethodMapping[$step[0]]), $step[1]);
                if ($return["filesize"]) {
                    verboseMessage("current filesize of the backup is: " . $return["filesize"]);
                }
            }
        }


// maintenance
        if ($config["maintenance"] == true) {
            verboseMessage("------------------------------------------------");
            verboseMessage("set maintenance mode off");
            Pimcore\Tool\Admin::deactivateMaintenanceMode();
        }


        verboseMessage("------------------------------------------------");
        verboseMessage("------------------------------------------------");
        /*
         * do not remove the string "backup finished"
         * deployment will check for this string to ensure that the backup has been successfully created
         * and no fatal error occurred during backup-creation
         */
        verboseMessage("backup finished, you can find your backup here: " . $backupFile);
    }

    protected function verboseMessage($m) {
        if($input->getOption("verbose")) {
            $this->output->writeln($m);
        }
    }
}