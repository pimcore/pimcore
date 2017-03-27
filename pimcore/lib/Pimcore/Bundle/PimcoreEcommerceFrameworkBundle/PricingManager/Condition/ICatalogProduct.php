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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Condition;

interface ICatalogProduct extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\ICondition
{
    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct[] $products
     *
     * @return ICatalogProduct
     */
    public function setProducts(array $products);

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct[]
     */
    public function getProducts();
}
