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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Command\Voucher;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupStatisticsCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('ecommerce:voucher:cleanup-statistics');
        $this->setDescription('House keeping for Voucher Usage Statistics - cleans up all old data.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output->writeln('<comment>*</comment> Cleaning up <info>statistics</info>');
        Factory::getInstance()->getVoucherService()->cleanUpStatistics();

        return 0;
    }
}
