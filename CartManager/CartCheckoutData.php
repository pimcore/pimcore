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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager;

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
