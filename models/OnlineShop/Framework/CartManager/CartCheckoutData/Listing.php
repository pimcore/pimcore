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

namespace OnlineShop\Framework\CartManager\CartCheckoutData;

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
