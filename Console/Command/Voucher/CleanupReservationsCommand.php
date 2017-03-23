<?php

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Console\Command\Voucher;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupReservationsCommand extends AbstractVoucherCommand
{
    protected function configure()
    {
        $this->setName('ecommerce:voucher:cleanup-reservations');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cleanupReservations();
    }
}
