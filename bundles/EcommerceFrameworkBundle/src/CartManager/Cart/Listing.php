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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;

/**
 * @method \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart[] load()
 * @method \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart|false current()
 * @method int getTotalCount()
 * @method \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart\Listing\Dao getDao()
 * @method void setCartClass(string $cartClass)
 */
class Listing extends \Pimcore\Model\Listing\AbstractListing
{
    public function __construct()
    {
        $this->getDao()->setCartClass(Factory::getInstance()->getCartManager()->getCartClassName());
    }

    /**
     * @param string $key The key to check
     *
     * @return bool
     */
    public function isValidOrderKey(string $key): bool
    {
        return in_array($key, ['userId', 'name', 'creationDateTimestamp', 'modificationDateTimestamp']);
    }

    public function getCarts(): array
    {
        return $this->getData();
    }

    public function setCarts(array $carts): static
    {
        return $this->setData($carts);
    }
}
