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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Config;
use Pimcore\Console\AbstractCommand;
use Pimcore\Tool\Admin;
use Pimcore\Tool\Console;
use Pimcore\Update;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UpdateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('pimcore:update')
            ->setAliases(['update'])
            ->setDescription('Update pimcore to the desired version/build')
            ->addOption(
                'list', 'l',
                InputOption::VALUE_NONE,
                'List available updates'
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
            )->addOption(
                'source-build', null,
                InputOption::VALUE_OPTIONAL,
                'specify a source build where the update should start from - this is mainly for debugging purposes'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentRevision = null;
        if ($input->getOption('source-build')) {
            $currentRevision = $input->getOption('source-build');
        }

        $availableUpdates = Update::getAvailableUpdates($currentRevision);

        if ($input->getOption('list')) {
            if (count($availableUpdates['releases'])) {
                $rows = [];
                foreach ($availableUpdates['releases'] as $release) {
                    $rows[] = [$release['version'], date('Y-m-d', $release['date']), $release['id']];
                }

                $table = new Table($output);
                $table
                    ->setHeaders(['Version', 'Date', 'Build'])
                    ->setRows($rows);
                $table->render();
            }

            if (count($availableUpdates['revisions'])) {
                $this->output->writeln('The latest available build is: <comment>' . $availableUpdates['revisions'][0]['id'] . '</comment> (' . date('Y-m-d', $availableUpdates['revisions'][0]['date']) . ')');
            }

            if (!count($availableUpdates['releases']) && !count($availableUpdates['revisions'])) {
                $this->output->writeln('<info>No updates available</info>');
            }
        }

        if ($input->getOption('update')) {
            $returnMessages = [];
            $build = null;
            $updateInfo = trim($input->getOption('update'));
            if (is_numeric($updateInfo)) {
                $build = $updateInfo;
            } else {
                // get build nr. by version number
                foreach ($availableUpdates['releases'] as $release) {
                    if ($release['version'] == $updateInfo) {
                        $build = $release['id'];
                        break;
                    }
                }
            }

            if (!$build) {
                $this->writeError('Update with build / version ' . $updateInfo . ' not found.');
                exit;
            }

            $debug = \Pimcore::inDebugMode() || in_array(Config::getEnvironment(), ['dev', 'test']);
            if (!$debug) {
                $this->writeError('Enable debug mode in system settings or set PIMCORE_ENVIRONMENT=dev');
                exit;
            }

            if (!Update::isWriteable()) {
                $this->writeError(PIMCORE_PATH . ' is not recursivly writable, please check!');
                exit;
            }

            if (!Update::isComposerAvailable()) {
                $this->writeError('Composer is not installed properly, please ensure composer is in your PATH variable.');
                exit;
            }

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("You are going to update to build $build! Continue with this action? (y/n)", false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }

            $this->output->writeln('Starting the update process ...');
            if ($input->getOption('dry-run')) {
                $this->output->writeln('<info>---------- DRY-RUN ----------</info>');
            }

            $jobs = Update::getJobs($build, $currentRevision);

            $steps = count($jobs['download']) + count($jobs['update']);

            $progress = new ProgressBar($output, $steps);
            $progress->start();

            foreach ($jobs['download'] as $job) {
                if ($job['type'] == 'download') {
                    Update::downloadData($job['revision'], $job['url']);
                }

                $progress->advance();
            }

            $maintenanceModeId = 'cache-warming-dummy-session-id';
            Admin::activateMaintenanceMode($maintenanceModeId);

            $stoppedByError = false;
            foreach ($jobs['update'] as $job) {
                if ($input->getOption('dry-run')) {
                    $job['dry-run'] = true;
                }

                $script = realpath(PIMCORE_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console');
                $return = Console::runPhpScript($script, 'internal:update-processor ' . escapeshellarg(json_encode($job)));

                $return = trim($return);

                $returnData = @json_decode($return, true);
                if (is_array($returnData)) {
                    if (trim($returnData['message'])) {
                        $returnMessages[] = [$job['revision'], strip_tags($returnData['message'])];
                    }

                    if (!$returnData['success']) {
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

            Update::composerDumpAutoload();

            Admin::deactivateMaintenanceMode();

            $this->output->writeln("\n");

            if ($stoppedByError) {
                $this->output->writeln('<error>Update stopped by error! Please check your logs</error>');
                $this->output->writeln('Last return value was: ' . $return);
            } else {
                $this->output->writeln('<info>Update done!</info>');

                if (count($returnMessages)) {
                    $table = new Table($output);
                    $table
                        ->setHeaders(['Build', 'Message'])
                        ->setRows($returnMessages);
                    $table->render();
                }
            }
        }
    }
}
