<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\CartManager\CartItem;

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
