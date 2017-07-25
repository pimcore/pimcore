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
use Pimcore\Event\System\MaintenanceEvent;
use Pimcore\Event\SystemEvents;
use Pimcore\Logger;
use Pimcore\Model\Schedule;
use Pimcore\Model\Schedule\Maintenance\Job;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceCommand extends AbstractCommand
{
    protected $systemTasks = [
        'scheduledtasks', 'cleanupcache', 'logmaintenance', 'sanitycheck', 'cleanuplogfiles', 'versioncleanup',
        'versioncompress', 'redirectcleanup', 'cleanupbrokenviews', 'usagestatistics', 'downloadmaxminddb',
        'tmpstorecleanup', 'imageoptimize'
    ];

    protected function configure()
    {
        $description = 'Asynchronous maintenance jobs of pimcore (needs to be set up as cron job)';

        $help = $description . '. Valid jobs are: ' . "\n\n";
        $help .= '  <comment>*</comment> any bundle class name handling maintenance (e.g. <comment>PimcoreEcommerceFrameworkBundle</comment>)' . "\n";

        foreach ($this->systemTasks as $systemTask) {
            $help .= '  <comment>*</comment> ' . $systemTask . "\n";
        }

        $this
            ->setName('pimcore:maintenance')
            ->setAliases(['maintenance'])
            ->setDescription($description)
            ->setHelp($help)
            ->addOption(
                'job',
                'j',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Call just a specific job(s) (see <comment>--help</comment> for a list of valid jobs)'
            )
            ->addOption(
                'excludedJobs',
                'J',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Exclude specific job(s) (see <comment>--help</comment> for a list of valid jobs)'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Run the jobs, regardless if they\'re locked or not'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $validJobs    = $this->getArrayOptionValue($input, 'job');
        $excludedJobs = $this->getArrayOptionValue($input, 'excludedJobs');

        // create manager
        $manager = $this->getContainer()->get('pimcore.maintenance.schedule_manager');
        $manager->setValidJobs($validJobs);
        $manager->setExcludedJobs($excludedJobs);
        $manager->setForce((bool) $input->getOption('force'));

        // register scheduled tasks
        $manager->registerJob(Job::fromMethodCall('scheduledtasks', new Schedule\Task\Executor(), 'execute'));
        $manager->registerJob(Job::fromMethodCall('logmaintenance', new \Pimcore\Log\Maintenance(), 'mail'));
        $manager->registerJob(Job::fromMethodCall('cleanuplogfiles', new \Pimcore\Log\Maintenance(), 'cleanupLogFiles'));
        $manager->registerJob(Job::fromMethodCall('httperrorlog', new \Pimcore\Log\Maintenance(), 'httpErrorLogCleanup'));
        $manager->registerJob(Job::fromMethodCall('usagestatistics', new \Pimcore\Log\Maintenance(), 'usageStatistics'));
        $manager->registerJob(Job::fromMethodCall('checkErrorLogsDb', new \Pimcore\Log\Maintenance(), 'checkErrorLogsDb'));
        $manager->registerJob(Job::fromMethodCall('archiveLogEntries', new \Pimcore\Log\Maintenance(), 'archiveLogEntries'));
        $manager->registerJob(Job::fromMethodCall('sanitycheck', '\\Pimcore\\Model\\Element\\Service', 'runSanityCheck'));
        $manager->registerJob(Job::fromMethodCall('versioncleanup', new \Pimcore\Model\Version(), 'maintenanceCleanUp'));
        $manager->registerJob(Job::fromMethodCall('versioncompress', new \Pimcore\Model\Version(), 'maintenanceCompress'));
        $manager->registerJob(Job::fromMethodCall('redirectcleanup', '\\Pimcore\\Model\\Redirect', 'maintenanceCleanUp'));
        $manager->registerJob(Job::fromMethodCall('cleanupbrokenviews', '\\Pimcore\\Db', 'cleanupBrokenViews'));
        $manager->registerJob(Job::fromMethodCall('downloadmaxminddb', '\\Pimcore\\Update', 'updateMaxmindDb'));
        $manager->registerJob(Job::fromMethodCall('cleanupcache', '\\Pimcore\\Cache', 'maintenance'));
        $manager->registerJob(Job::fromMethodCall('tmpstorecleanup', '\\Pimcore\\Model\\Tool\\TmpStore', 'cleanup'));
        $manager->registerJob(Job::fromMethodCall('imageoptimize', '\\Pimcore\\Model\\Asset\\Image\\Thumbnail\\Processor', 'processOptimizeQueue'));
        $manager->registerJob(Job::fromMethodCall('cleanupTmpFiles', '\\Pimcore\\Tool\\Housekeeping', 'cleanupTmpFiles'));

        $event = new MaintenanceEvent($manager);
        \Pimcore::getEventDispatcher()->dispatch(SystemEvents::MAINTENANCE, $event);

        $manager->run();

        Logger::info('All maintenance-jobs finished!');
    }

    /**
     * Get an array option value, but still support the value being comma-separated for backwards compatibility
     *
     * @param InputInterface $input
     * @param string $name
     *
     * @return array
     */
    private function getArrayOptionValue(InputInterface $input, string $name): array
    {
        $value  = $input->getOption($name);
        $result = [];

        if (!empty($value)) {
            foreach ($value as $val) {
                foreach (explode(',', $val) as $part) {
                    $part = trim($part);
                    if (!empty($part)) {
                        $result[] = $part;
                    }
                }
            }
        }

        $result = array_unique($result);

        return $result;
    }
}
