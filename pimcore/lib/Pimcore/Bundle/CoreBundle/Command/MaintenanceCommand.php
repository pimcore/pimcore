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
                'job', 'j',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Call just a specific job(s) (see <comment>--help</comment> for a list of valid jobs)'
            )
            ->addOption(
                'excludedJobs', 'J',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Exclude specific job(s) (see <comment>--help</comment> for a list of valid jobs)'
            )
            ->addOption(
                'force', 'f',
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
        $manager = Schedule\Manager\Factory::getManager('maintenance.pid');
        $manager->setValidJobs($validJobs);
        $manager->setExcludedJobs($excludedJobs);
        $manager->setForce((bool) $input->getOption('force'));

        // register scheduled tasks
        $manager->registerJob(new Schedule\Maintenance\Job('scheduledtasks', new Schedule\Task\Executor(), 'execute'));
        $manager->registerJob(new Schedule\Maintenance\Job('logmaintenance', new \Pimcore\Log\Maintenance(), 'mail'));
        $manager->registerJob(new Schedule\Maintenance\Job('cleanuplogfiles', new \Pimcore\Log\Maintenance(), 'cleanupLogFiles'));
        $manager->registerJob(new Schedule\Maintenance\Job('httperrorlog', new \Pimcore\Log\Maintenance(), 'httpErrorLogCleanup'));
        $manager->registerJob(new Schedule\Maintenance\Job('usagestatistics', new \Pimcore\Log\Maintenance(), 'usageStatistics'));
        $manager->registerJob(new Schedule\Maintenance\Job('checkErrorLogsDb', new \Pimcore\Log\Maintenance(), 'checkErrorLogsDb'));
        $manager->registerJob(new Schedule\Maintenance\Job('archiveLogEntries', new \Pimcore\Log\Maintenance(), 'archiveLogEntries'));
        $manager->registerJob(new Schedule\Maintenance\Job('sanitycheck', '\\Pimcore\\Model\\Element\\Service', 'runSanityCheck'));
        $manager->registerJob(new Schedule\Maintenance\Job('versioncleanup', new \Pimcore\Model\Version(), 'maintenanceCleanUp'));
        $manager->registerJob(new Schedule\Maintenance\Job('versioncompress', new \Pimcore\Model\Version(), 'maintenanceCompress'));
        $manager->registerJob(new Schedule\Maintenance\Job('redirectcleanup', '\\Pimcore\\Model\\Redirect', 'maintenanceCleanUp'));
        $manager->registerJob(new Schedule\Maintenance\Job('cleanupbrokenviews', '\\Pimcore\\Db', 'cleanupBrokenViews'));
        $manager->registerJob(new Schedule\Maintenance\Job('downloadmaxminddb', '\\Pimcore\\Update', 'updateMaxmindDb'));
        $manager->registerJob(new Schedule\Maintenance\Job('cleanupcache', '\\Pimcore\\Cache', 'maintenance'));
        $manager->registerJob(new Schedule\Maintenance\Job('tmpstorecleanup', '\\Pimcore\\Model\\Tool\\TmpStore', 'cleanup'));
        $manager->registerJob(new Schedule\Maintenance\Job('imageoptimize', '\\Pimcore\\Model\\Asset\\Image\\Thumbnail\\Processor', 'processOptimizeQueue'));
        $manager->registerJob(new Schedule\Maintenance\Job('cleanupTmpFiles', '\\Pimcore\\Tool\\Housekeeping', 'cleanupTmpFiles'));

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
