<?php

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Console\Command\Voucher;

use OnlineShop\Framework\Factory;
use Pimcore\Console\AbstractCommand;

abstract class AbstractVoucherCommand extends AbstractCommand
{
    protected function cleanupReservations()
    {
        $this->output->writeln('<comment>*</comment> Cleaning up <info>reservations</info>');
        Factory::getInstance()->getVoucherService()->cleanUpReservations();
    }

    protected function cleanupStatistics()
    {
        $this->output->writeln('<comment>*</comment> Cleaning up <info>statistics</info>');
        Factory::getInstance()->getVoucherService()->cleanUpStatistics();
    }
}
