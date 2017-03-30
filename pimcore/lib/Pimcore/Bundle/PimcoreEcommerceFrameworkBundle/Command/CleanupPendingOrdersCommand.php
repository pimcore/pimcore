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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Command;

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
        $this->setDescription("Cleans up orders with state pending payment after 1h -> delegates this to commit order processor");
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
