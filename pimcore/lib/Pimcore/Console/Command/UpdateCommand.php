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

use Pimcore\Console\AbstractCommand;
use Pimcore\Tool\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Pimcore\Update;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UpdateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update pimcore to the desired version/build')
            ->addOption(
                'list', 'l',
                InputOption::VALUE_NONE,
                "List available updates"
            )
            ->addOption(
                'update', 'u',
                InputOption::VALUE_OPTIONAL,
                'Update to the given number / build'
            )
            ->addOption(
                'dry-run', 'd',
                InputOption::VALUE_NONE,
                'Dry-run'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $availableUpdates = Update::getAvailableUpdates();

        if($input->getOption("list")) {

            if(count($availableUpdates["releases"])) {
                $rows = [];
                foreach ($availableUpdates["releases"] as $release) {
                    $rows[] = [$release["version"], date("Y-m-d", $release["date"]), $release["id"]];
                }

                $table = new Table($output);
                $table
                    ->setHeaders(array('Version', 'Date', 'Build'))
                    ->setRows($rows);
                $table->render();
            }

            if(count($availableUpdates["revisions"])) {
                $this->output->writeln("The latest available build is: <comment>" . $availableUpdates["revisions"][0]["id"] . "</comment> (" . date("Y-m-d", $availableUpdates["revisions"][0]["date"]) . ")");
            }
        }

        if($input->getOption("update")) {

            $returnMessages = [];
            $build = null;
            $updateInfo = trim($input->getOption("update"));
            if(is_numeric($updateInfo)) {
                $build = $updateInfo;
            } else {
                // get build nr. by version number
                foreach ($availableUpdates["releases"] as $release) {
                    if($release["version"] == $updateInfo) {
                        $build = $release["id"];
                        break;
                    }
                }
            }

            if(!$build) {
                $this->writeError("Update with build / version " . $updateInfo . " not found.");
                exit;
            }

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("You are going to update to build $build! Continue with this action? (y/n)", false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }

            $this->output->writeln("Starting the update process ...");
            if($input->getOption("dry-run")) {
                $this->output->writeln("<info>---------- DRY-RUN ----------</info>");
            }

            $jobs = Update::getJobs($build);

            $steps = count($jobs["parallel"]) + count($jobs["procedural"]);

            $progress = new ProgressBar($output, $steps);
            $progress->start();

            foreach($jobs["parallel"] as $job) {
                if($job["type"] == "download") {
                    Update::downloadData($job["revision"], $job["url"]);
                }

                $progress->advance();
            }

            $stoppedByError = false;
            foreach($jobs["procedural"] as $job) {

                if($input->getOption("dry-run")) {
                    $job["dry-run"] = true;
                }

                $phpCli = Console::getPhpCli();

                $cmd = $phpCli . " " . realpath(PIMCORE_PATH . DIRECTORY_SEPARATOR . "cli" . DIRECTORY_SEPARATOR . "console.php"). " internal:update-processor " . escapeshellarg(json_encode($job));
                $return = Console::exec($cmd);

                $return = trim($return);

                $returnData = @json_decode($return, true);
                if(is_array($returnData)) {

                    if(trim($returnData["message"])) {
                        $returnMessages[] = [$job["revision"], strip_tags($returnData["message"])];
                    }

                    if(!$returnData["success"]) {
                        $stoppedByError = true;
                        break;
                    }
                } else {
                    $stoppedByError = true;
                    break;
                }

                $progress->advance();
            }

            $progress->finish();

            $this->output->writeln("\n");

            if($stoppedByError) {
                $this->output->writeln("<error>Update stopped by error! Please check your logs</error>");
                $this->output->writeln("Last return value was: " . $return);
            } else {
                $this->output->writeln("<info>Update done!</info>");

                if(count($returnMessages)) {
                    $table = new Table($output);
                    $table
                        ->setHeaders(array('Build', 'Message'))
                        ->setRows($returnMessages);
                    $table->render();
                }
            }
        }
    }
}
