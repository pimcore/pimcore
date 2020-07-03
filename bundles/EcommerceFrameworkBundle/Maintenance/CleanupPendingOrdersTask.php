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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Maintenance;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Maintenance\TaskInterface;

class CleanupPendingOrdersTask implements TaskInterface
{
    public function execute()
    {
        $checkoutManager = Factory::getInstance()->getCheckoutManager(new Cart());
        $checkoutManager->cleanUpPendingOrders();
    }
}
