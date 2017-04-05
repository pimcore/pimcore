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

class CleanupReservationsCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('ecommerce:voucher:cleanup-reservations');
        $this->setDescription("Cleans the token reservations due to sysConfig duration settings");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output->writeln('<comment>*</comment> Cleaning up <info>reservations</info>');
        Factory::getInstance()->getVoucherService()->cleanUpReservations();
    }
}
