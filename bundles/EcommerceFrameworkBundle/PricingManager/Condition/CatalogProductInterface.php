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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;

interface CatalogProductInterface extends ConditionInterface
{
    /**
     * @param AbstractProduct[] $products
     *
     * @return CatalogProductInterface
     */
    public function setProducts(array $products);

    /**
     * @return AbstractProduct[]
     */
    public function getProducts();
}

class_alias(CatalogProductInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\ICatalogProduct');
