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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct;

/**
 * @deprecated Use ICartProductActionRemove instead
 */
interface IProductActionRemove
{
    /**
     * Track product remove from cart
     *
     * @deprecated Use ICartProductActionRemove::trackCartProductActionRemove instead
     *
     * @param IProduct $product
     * @param int|float $quantity
     */
    public function trackProductActionRemove(IProduct $product, $quantity = 1);
}
