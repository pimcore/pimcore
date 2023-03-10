<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder as Order;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem as OrderItem;

interface OrderListItemInterface
{
    public function getId(): int;

    public function reference(): OrderItem|Order|null;
}
