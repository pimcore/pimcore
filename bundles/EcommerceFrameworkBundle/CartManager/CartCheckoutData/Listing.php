<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData;

/**
 * @method CartCheckoutData[] load()
 * @method CartCheckoutData current()
 * @method int getTotalCount()
 * @method \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData\Listing\Dao getDao()
 */
class Listing extends \Pimcore\Model\Listing\AbstractListing
{
    /**
     * @param string $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        if ($key == 'key' || $key == 'cartId') {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getCartCheckoutDataItems()
    {
        return $this->getData();
    }

    /**
     * @param array $cartCheckoutDataItems
     *
     * @return self
     */
    public function setCartCheckoutDataItems($cartCheckoutDataItems)
    {
        return $this->setData($cartCheckoutDataItems);
    }
}
