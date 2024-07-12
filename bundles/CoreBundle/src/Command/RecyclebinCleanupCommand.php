<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\Command;

use DateTime;
use Exception;
use Pimcore\Console\AbstractCommand;
use Pimcore\Logger;
use Pimcore\Model\Element\Recyclebin;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:recyclebin:cleanup',
    description: 'Cleanup recyclebin entries'
)]
class RecyclebinCleanupCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'older-than-days',
                'd',
                InputOption::VALUE_REQUIRED,
                'Older than X Days to delete recyclebin entries'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $daysAgo = $input->getOption('older-than-days');

        if (!isset($daysAgo)) {
            throw new Exception('Missing option "--older-than-days"');
        } elseif (!is_numeric($daysAgo)) {
            throw new Exception('The "--older-than-days" option value should be numeric');
        }

        $date = new DateTime("-{$daysAgo} days");
        $dateTimestamp = $date->getTimestamp();
        $recyclebinItems = new Recyclebin\Item\Listing();
        $recyclebinItems->setCondition("date < $dateTimestamp");

        foreach ($recyclebinItems->load() as $recyclebinItem) {
            try {
                $recyclebinItem->delete();
            } catch (Exception $e) {
                $msg = "Could not delete {$recyclebinItem->getPath()} ({$recyclebinItem->getId()}) because of: {$e->getMessage()}";
                Logger::error($msg);
                $this->output->writeln($msg);
            }
        }

        $this->output->writeln('Recyclebin cleanup done!');

        return 0;
    }
}
