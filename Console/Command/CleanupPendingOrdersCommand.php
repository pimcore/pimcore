<?php

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Console\Command;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\Cart;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory;
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
     * @throws InvalidConfigException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $checkoutManager = Factory::getInstance()->getCheckoutManager(new Cart());
        $checkoutManager->cleanUpPendingOrders();
    }
}
