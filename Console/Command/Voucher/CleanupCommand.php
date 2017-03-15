<?php

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Console\Command\Voucher;

use OnlineShop\Framework\CartManager\Cart;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommand extends AbstractVoucherCommand
{
    protected function configure()
    {
        $this->setName('shop:voucher:cleanup');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cleanupReservations();
        $this->cleanupStatistics();
    }
}
