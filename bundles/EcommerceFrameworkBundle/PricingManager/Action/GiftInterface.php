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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ActionInterface;

/**
 * Adds a gift product to the given cart
 */
interface GiftInterface extends ActionInterface
{
    /**
     * Set gift product
     *
     * @param AbstractProduct $product
     *
     * @return GiftInterface
     */
    public function setProduct(AbstractProduct $product);

    /**
     * @return AbstractProduct
     */
    public function getProduct();
}

class_alias(GiftInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\IGift');
