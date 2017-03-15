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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartCheckoutData;

class Listing extends \Pimcore\Model\Listing\AbstractListing {

    /**
     * @var array
     */
    public $cartCheckoutDataItems;

    /**
     * @var array
     */
    public function isValidOrderKey($key) {
        if($key == "key" || $key == "cartId") {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    function getCartCheckoutDataItems() {
        if(empty($this->cartCheckoutDataItems)) {
            $this->load();
        }
        return $this->cartCheckoutDataItems;
    }

    /**
     * @param array $cartCheckoutDataItems
     * @return void
     */
    function setCartCheckoutDataItems($cartCheckoutDataItems) {
        $this->cartCheckoutDataItems = $cartCheckoutDataItems;
    }
}
