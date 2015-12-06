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

namespace OnlineShop\Framework\CartManager\Cart;

class Listing extends \Pimcore\Model\Listing\AbstractListing {

    /**
     * @var array
     */
    public $carts;

    public function __construct() {
        $this->getDao()->setCartClass(\OnlineShop\Framework\Factory::getInstance()->getCartManager()->getCartClassName());
    }

    /**
     * @var array
     */
    public function isValidOrderKey($key) {
        if($key == "userId" || $key == "name") {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    function getCarts() {
        if(empty($this->carts)) {
            $this->load();
        }
        return $this->carts;
    }

    /**
     * @param array $carts
     * @return void
     */
    function setCarts($carts) {
        $this->carts = $carts;
    }

}
