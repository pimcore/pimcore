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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager;

use \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder as Order;
use \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrderItem as OrderItem;

interface IOrderListItem
{
    /**
     * @return int
     */
    public function getId();


    /**
     * @return Order|OrderItem
     */
    public function reference();
}