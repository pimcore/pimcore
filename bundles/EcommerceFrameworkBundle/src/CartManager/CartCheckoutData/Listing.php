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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData;

/**
 * @method CartCheckoutData[] load()
 * @method CartCheckoutData|false current()
 * @method int getTotalCount()
 * @method \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartCheckoutData\Listing\Dao getDao()
 */
class Listing extends \Pimcore\Model\Listing\AbstractListing
{
    public function isValidOrderKey(string $key): bool
    {
        if ($key == 'key' || $key == 'cartId') {
            return true;
        }

        return false;
    }

    public function getCartCheckoutDataItems(): array
    {
        return $this->getData();
    }

    public function setCartCheckoutDataItems(array $cartCheckoutDataItems): Listing
    {
        return $this->setData($cartCheckoutDataItems);
    }
}
