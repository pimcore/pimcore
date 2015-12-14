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

namespace OnlineShop\Framework\CartManager;

class CartCheckoutData extends AbstractCartCheckoutData {

    public function save() {
        $this->getDao()->save();
    }

    public static function getByKeyCartId($key, $cartId) {
        $cacheKey = \OnlineShop\Framework\CartManager\CartCheckoutData\Dao::TABLE_NAME . "_" . $key . "_" . $cartId;

        try {
            $checkoutDataItem = \Zend_Registry::get($cacheKey);
        }
        catch (\Exception $e) {

            try {
                $checkoutDataItem = new self();
                $checkoutDataItem->getDao()->getByKeyCartId($key, $cartId);
                \Zend_Registry::set($cacheKey, $checkoutDataItem);
            } catch(\Exception $ex) {
                \Logger::debug($ex->getMessage());
                return null;
            }

        }

        return $checkoutDataItem;
    }

    public static function removeAllFromCart($cartId) {
        $checkoutDataItem = new self();
        $checkoutDataItem->getDao()->removeAllFromCart($cartId);
    }

}
