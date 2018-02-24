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

use Pimcore\Console\AbstractCommand;
use Pimcore\Tool\Admin;
use Pimcore\Tool\Console;
use Pimcore\Update;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('pimcore:update')
            ->setAliases(['update'])
            ->setDescription('Update pimcore to the desired version/build')
            ->addOption(
                'list',
                'l',
                InputOption::VALUE_NONE,
                'List available updates'
            )
            ->addOption(
                'update',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Update to the given number / build'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Dry-run'
            )->addOption(
                'source-build',
                null,
                InputOption::VALUE_OPTIONAL,
                'specify a source build where the update should start from - this is mainly for debugging purposes'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // remove terminate event listeners as they break with a cleared container
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        foreach ($eventDispatcher->getListeners(ConsoleEvents::TERMINATE) as $listener) {
            $eventDispatcher->removeListener(ConsoleEvents::TERMINATE, $listener);
        }

        $currentRevision = null;
        if ($input->getOption('source-build')) {
            $currentRevision = $input->getOption('source-build');
        }

        $this->output->writeln('Fetching available updates...');
        $availableUpdates = Update::getAvailableUpdates($currentRevision);

        if ($input->getOption('list')) {
            if (count($availableUpdates['releases'])) {
                $rows = [];
                foreach ($availableUpdates['releases'] as $release) {
                    $rows[] = [$release['version'], date('Y-m-d', $release['date']), $release['id']];
                }

                $this->io->newLine();

                $table = new Table($output);
                $table
                    ->setHeaders(['Version', 'Date', 'Build'])
                    ->setRows($rows)
                    ->render();

                $this->io->newLine();
            }

            if (count($availableUpdates['revisions'])) {
                $this->io->writeln('The latest available build is: <comment>' . $availableUpdates['revisions'][0]['id'] . '</comment> (' . date('Y-m-d', $availableUpdates['revisions'][0]['date']) . ')');
            }

            if (!count($availableUpdates['releases']) && !count($availableUpdates['revisions'])) {
                $this->io->writeln('<info>No updates available</info>');
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

            $debug = $this->getApplication()->getKernel()->isDebug();
            if (!$debug) {
                $this->writeError('Enable debug mode in system settings or set PIMCORE_ENVIRONMENT=dev');
                exit;
            }

            if (!Update::isWriteable()) {
                $this->writeError(PIMCORE_PATH . ' is not recursively writable, please check!');
                exit;
            }

            if (!Update::isComposerAvailable()) {
                $this->writeError('Composer is not installed properly, please ensure composer is in your PATH variable.');
                exit;
            }

            $questionResult = $this->io->confirm(
                sprintf('You are going to update to build <comment>%s</comment>! Do you want to continue?', $build),
                false
            );

            if (!$input->getOption('no-interaction') && !$questionResult) {
                return;
            }

            if ($input->getOption('dry-run')) {
                $this->io->writeln('<info>---------- DRY-RUN ----------</info>');
            }

            $jobs = Update::getJobs($build, $currentRevision);

            $steps = count($jobs['download']) + count($jobs['update']);

            $this->io->newLine();

            $progress = new ProgressBar($output, $steps);
            $progress->setMessage('Starting the update process...');
            $progress->setFormat("<comment>%message%</comment>\n\n %current%/%max% [%bar%] %percent:3s%%");
            $progress->start();

            foreach ($jobs['download'] as $job) {
                if ($job['type'] == 'download') {
                    $progress->setMessage(sprintf('Downloading update <comment>%s</comment>', $job['revision']));
                    Update::downloadData($job['revision'], $job['url']);
                }

                $progress->advance();
            }

            $progress->setMessage('Activating maintenance mode before applying updates...');

            $maintenanceModeId = 'cache-warming-dummy-session-id';
            Admin::activateMaintenanceMode($maintenanceModeId);

            $jobResults = [
                'success' => [],
                'error'   => []
            ];

            $stoppedByError = false;
            foreach ($jobs['update'] as $job) {
                if ($input->getOption('dry-run')) {
                    $job['dry-run'] = true;
                }

                $progress->setMessage(sprintf('Running job <comment>%s</comment>', json_encode($job)));

                $script = realpath(PIMCORE_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console');
                $return = Console::runPhpScript($script, 'internal:update-processor --ignore-maintenance-mode ' . escapeshellarg(json_encode($job)));

                $return = trim($return);

                $returnData = @json_decode($return, true);
                if (is_array($returnData)) {
                    if (trim($returnData['message'] ?? null)) {
                        $returnMessages[] = [$job['revision'], strip_tags($returnData['message'])];
                    }

                    if ($returnData['success'] ?? false) {
                        $jobResults['success'][] = $job;
                    } else {
                        $stoppedByError = true;
                        break;
                    }
                } else {
                    $stoppedByError = true;
                    break;
                }

                if ($stoppedByError) {
                    $jobResults['error'][] = $job;
                }

                $progress->advance();
            }

            if (!$stoppedByError) {
                $progress->finish();
            }

            $this->io->newLine(2);

            $this->io->writeln('Running composer update...');
            Update::composerUpdate();

            $this->io->writeln('Deactivating maintenance mode...');
            Admin::deactivateMaintenanceMode();

            $this->io->newLine(1);

            if (count($jobResults['error']) > 0 || $output->isVerbose()) {
                $this->io->section('Scheduled jobs');
                $this->io->listing(array_map('json_encode', $jobs['update']));

                if (count($jobResults['success']) > 0) {
                    $this->io->section('Successful jobs');
                    $this->io->listing(array_map('json_encode', $jobResults['success']));
                }

                if (count($jobResults['error']) > 0) {
                    $this->io->section('Erroneous jobs');
                    $this->io->listing(array_map('json_encode', $jobResults['error']));
                }
            }

            // the exit() calls are necessary as we need to prevent any code running after the update which potentially
            // relies on services which don't exist anymore due to an updated container - see #2434
            if ($stoppedByError) {
                $this->io->error(sprintf('Update %s was stopped by error. Please check your logs.', $job['revision']));

                $this->io->writeln('Erroneous job was: ' . json_encode($job));
                $this->io->writeln('Last return value was: ' . $return);

                exit(1);
            } else {
                $this->io->success('Update done!');

                if (count($returnMessages)) {
                    $table = new Table($output);
                    $table
                        ->setHeaders(['Build', 'Message'])
                        ->setRows($returnMessages)
                        ->render();
                }

                exit(0);
            }
        }
    }
}
