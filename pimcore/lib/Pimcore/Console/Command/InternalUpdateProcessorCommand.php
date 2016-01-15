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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\Asset;
use Pimcore\Update;

class InternalUpdateProcessorCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('internal:update-processor')
            ->setDescription('For internal use only')
            ->addArgument("config");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $status = ["success" => true];
        $config = $input->getArgument("config");

        if($config) {
            $job = json_decode($config, true);

            if(is_array($job)) {

                if (isset($job["dry-run"])) {
                    // do not do anything here
                    \Logger::info("skipped update job because it is in dry-run mode", $job);
                } else if ($job["type"] == "files") {
                    Update::installData($job["revision"]);
                } else if ($job["type"] == "clearcache") {
                    \Pimcore\Cache::clearAll();
                } else if ($job["type"] == "preupdate") {
                    $status = Update::executeScript($job["revision"], "preupdate");
                } else if ($job["type"] == "postupdate") {
                    $status = Update::executeScript($job["revision"], "postupdate");
                } else if ($job["type"] == "cleanup") {
                    Update::cleanup();
                }
            }
        }

        $this->output->write(json_encode($status));
    }
}
