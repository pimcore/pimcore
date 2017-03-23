<?php

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Console\Command\Voucher;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommand extends AbstractVoucherCommand
{
    protected function configure()
    {
        $this->setName('ecommerce:voucher:cleanup');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cleanupReservations();
        $this->cleanupStatistics();
    }
}
