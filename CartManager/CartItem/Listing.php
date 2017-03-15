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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartItem;

class Listing extends \Pimcore\Model\Listing\AbstractListing {

    /**
     * @var array
     */
    public $cartItems;

    /**
     * @var array
     */
    protected $order = array('ASC');

    /**
     * @var array
     */
    protected $orderKey = array('`sortIndex`', '`addedDateTimestamp`');

    /**
     * @var array
     * @return boolean
     */
    public function isValidOrderKey($key) {
        if(in_array($key, ['productId', 'cartId', 'count', 'itemKey', 'addedDateTimestamp', 'sortIndex'])) {
            return true;
        }
        return false;
    }

    /**
     * @return \OnlineShop\Framework\CartManager\ICartItem[]
     */
    public function getCartItems() {
        if(empty($this->cartItems)) {
            $this->load();
        }
        return $this->cartItems;
    }

    /**
     * @param \OnlineShop\Framework\CartManager\ICartItem[] $cartItems
     * @return void
     */
    public function setCartItems($cartItems) {
        $this->cartItems = $cartItems;
    }

    /**
     * @param string $className
     */
    public function setCartItemClassName( $className )
    {
        $this->getDao()->setClassName( $className );
    }

}
