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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Console\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\Schedule;
use Pimcore\Logger;

class MaintenanceCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('maintenance')
            ->setDescription('Asynchronous maintenance jobs of pimcore (needs to be set up as cron job)')
            ->addOption(
                'job', 'j',
                InputOption::VALUE_OPTIONAL,
                'call just a specific job(s), use "," (comma) to execute more than one job (valid options: scheduledtasks, cleanupcache, logmaintenance, sanitycheck, cleanuplogfiles, versioncleanup, versioncompress, redirectcleanup, cleanupbrokenviews, usagestatistics, downloadmaxminddb, tmpstorecleanup, imageoptimize and plugin classes if you want to call a plugin maintenance)'
            )
            ->addOption(
                'force', 'f',
                InputOption::VALUE_NONE,
                "run the jobs, regardless if they're locked or not"
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $validJobs = [];
        if ($input->getOption("job")) {
            $validJobs = explode(",", $input->getOption("job"));
        }

        // create manager
        $manager = Schedule\Manager\Factory::getManager("maintenance.pid");
        $manager->setValidJobs($validJobs);
        $manager->setForce((bool) $input->getOption("force"));

        // register scheduled tasks
        $manager->registerJob(new Schedule\Maintenance\Job("scheduledtasks", new Schedule\Task\Executor(), "execute"));
        $manager->registerJob(new Schedule\Maintenance\Job("logmaintenance", new \Pimcore\Log\Maintenance(), "mail"));
        $manager->registerJob(new Schedule\Maintenance\Job("cleanuplogfiles", new \Pimcore\Log\Maintenance(), "cleanupLogFiles"));
        $manager->registerJob(new Schedule\Maintenance\Job("httperrorlog", new \Pimcore\Log\Maintenance(), "httpErrorLogCleanup"));
        $manager->registerJob(new Schedule\Maintenance\Job("usagestatistics", new \Pimcore\Log\Maintenance(), "usageStatistics"));
        $manager->registerJob(new Schedule\Maintenance\Job("checkErrorLogsDb", new \Pimcore\Log\Maintenance(), "checkErrorLogsDb"));
        $manager->registerJob(new Schedule\Maintenance\Job("archiveLogEntries", new \Pimcore\Log\Maintenance(), "archiveLogEntries"));
        $manager->registerJob(new Schedule\Maintenance\Job("sanitycheck", "\\Pimcore\\Model\\Element\\Service", "runSanityCheck"));
        $manager->registerJob(new Schedule\Maintenance\Job("versioncleanup", new \Pimcore\Model\Version(), "maintenanceCleanUp"));
        $manager->registerJob(new Schedule\Maintenance\Job("versioncompress", new \Pimcore\Model\Version(), "maintenanceCompress"));
        $manager->registerJob(new Schedule\Maintenance\Job("redirectcleanup", "\\Pimcore\\Model\\Redirect", "maintenanceCleanUp"));
        $manager->registerJob(new Schedule\Maintenance\Job("cleanupbrokenviews", "\\Pimcore\\Db", "cleanupBrokenViews"));
        $manager->registerJob(new Schedule\Maintenance\Job("downloadmaxminddb", "\\Pimcore\\Update", "updateMaxmindDb"));
        $manager->registerJob(new Schedule\Maintenance\Job("cleanupcache", "\\Pimcore\\Cache", "maintenance"));
        $manager->registerJob(new Schedule\Maintenance\Job("tmpstorecleanup", "\\Pimcore\\Model\\Tool\\TmpStore", "cleanup"));
        $manager->registerJob(new Schedule\Maintenance\Job("imageoptimize", "\\Pimcore\\Model\\Asset\\Image\\Thumbnail\\Processor", "processOptimizeQueue"));
        $manager->registerJob(new Schedule\Maintenance\Job("cleanupTmpFiles", "\\Pimcore\\Tool\\Housekeeping", "cleanupTmpFiles"));

        \Pimcore::getEventManager()->trigger("system.maintenance", $manager);

        $manager->run();

        Logger::info("All maintenance-jobs finished!");
    }
}
