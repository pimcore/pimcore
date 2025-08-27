<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\Command;

use DateTime;
use Exception;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Tool\Email;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:email:cleanup',
    description: 'Cleanup email logs',
    aliases: ['email:cleanup']
)]
class EmailLogsCleanupCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'older-than-days',
                'days',
                InputOption::VALUE_REQUIRED,
                'Older than X Days to delete email logs'
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
        $emailLogs = new Email\Log\Listing();
        $emailLogs->setCondition("sentDate < $dateTimestamp");

        foreach ($emailLogs->load() as $ekey => $emailLog) {
            $emailLog->delete();
        }

        $this->output->writeln('Email logs cleanup done!');

        return 0;
    }
}
