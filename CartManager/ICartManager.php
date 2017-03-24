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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IComponent;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable;

/**
 * Interface \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICartManager
 */
interface ICartManager extends IComponent {
 
    /**
     * returns cart class name configured in the onlineshop config
     *
     * Is also responsible for checking if guest cart class should be used or not,
     * by calling \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IEnvironment::getUseGuestCart();
     *
     *
     * @return string
     */
    public function getCartClassName();
    
    /**
     * adds item to given cart
     *
     * @param ICheckoutable  $product   - product to add
     * @param float                                                 $count
     * @param string                                                $key       - optional key of cart where the item should be added to
     * @param null|string                                           $itemKey   - optional item key
     * @param bool                                                  $replace   - replace item if same key already exists
     * @param array                                                 $params    - optional addtional item information
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractSetProductEntry[]        $subProducts
     * @param null|string                                           $comment
     *
     * @return string - item key
     */
    public function addToCart(ICheckoutable $product, $count,  $key = null, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null);

    /**
     * removes item from given cart
     *
     * @param string      $itemKey
     * @param null|string $key     - optional identification of cart in case of multi cart
     * @return void
     */
    public function removeFromCart($itemKey, $key = null);

    /**
     * returns cart
     *
     * @param null|string  $key - optional identification of cart in case of multi cart
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart
     */
    public function getCart($key = null);

    /**
     * returns cart by name
     *
     * @param string $name
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart
     */
    public function getCartByName($name);

    /**
     * returns all carts
     *
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart[]
     */
    public function getCarts();

    /**
     * clears given cart
     *
     * @param null|string  $key - optional identification of cart in case of multi cart
     * @return void
     */
    public function clearCart($key = null);

    /**
     * creates new cart
     *
     * @param  array   $param - array of cart information
     * @return string  key of new created cart
     */
    public function createCart($param);

    /**
     * deletes cart
     *
     * @param null|string  $key - optional identification of cart in case of multi cart
     * @return void
     */
    public function deleteCart($key = null);


    /**
     * creates price calculator for given cart
     *
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICartPriceCalculator
     */
    public function getCartPriceCalculator(ICart $cart);

    /**
     * @deprecated
     *
     * use getCartPriceCalculator instead
     *
     * @abstract
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICartPriceCalculator
     */
    public function getCartPriceCalcuator(ICart $cart);


    /**
     * resets cart manager - carts need to be reloaded after reset() is called
     *
     * @return void
     */
    public function reset();
}
