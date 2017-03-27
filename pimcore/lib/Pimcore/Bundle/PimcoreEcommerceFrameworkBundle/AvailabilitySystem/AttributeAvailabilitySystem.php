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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\AvailabilitySystem;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable;

/**
 * Class AttributeAvailabilitySystem
 */
class AttributeAvailabilitySystem implements IAvailabilitySystem
{
    /**
     * @param ICheckoutable $abstractProduct
     * @param int $quantityScale
     * @param null $products
     * @return IAvailability
     */
    public function getAvailabilityInfo(ICheckoutable $abstractProduct, $quantityScale = 1, $products = null)
    {
        return $abstractProduct;
    }
}
