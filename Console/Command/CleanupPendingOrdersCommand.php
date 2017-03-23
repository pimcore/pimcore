<?php

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Console\Command;

use OnlineShop\Framework\CartManager\Cart;
use OnlineShop\Framework\Factory;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupPendingOrdersCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('ecommerce:cleanup-pending-orders');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $checkoutManager = Factory::getInstance()->getCheckoutManager(new Cart());
        $checkoutManager->cleanUpPendingOrders();
    }
}
