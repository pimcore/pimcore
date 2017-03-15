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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\AvailabilitySystem;

/**
 * Class AttributeAvailabilitySystem
 */
class AttributeAvailabilitySystem implements IAvailabilitySystem {
    /**
     * @param \OnlineShop\Framework\Model\ICheckoutable $abstractProduct
     * @param int $quantityScale
     * @param null $products
     * @return \OnlineShop\Framework\AvailabilitySystem\IAvailability
     */
    public function getAvailabilityInfo(\OnlineShop\Framework\Model\ICheckoutable $abstractProduct, $quantityScale = 1, $products = null) {
        return $abstractProduct;
    }



}

