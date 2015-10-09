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


/**
 * Class OnlineShop_Framework_Impl_CachingPriceSystem
 *
 * price system which caches created price info objects per product and request
 *
 */
abstract class OnlineShop_Framework_Impl_CachingPriceSystem extends OnlineShop_Framework_Impl_AbstractPriceSystem implements OnlineShop_Framework_ICachingPriceSystem {

    /** @var OnlineShop_Framework_IPriceInfo[] $priceInfos  */
    protected $priceInfos = array();

    public function loadPriceInfos($productEntries, $options) {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__METHOD__  . " is not supported for " . get_class($this));
    }

    public function clearPriceInfos($productEntries, $options) {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__METHOD__  . " is not supported for " . get_class($this));
    }

    public function getPriceInfo(OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct, $quantityScale = 1, $products = null) {
        $pId = $abstractProduct->getId();
        if (!is_array($this->priceInfos[$pId])){
            $this->priceInfos[$pId] = array();
        }
        if (!$this->priceInfos[$pId][$quantityScale]){
            $priceInfo = $this->initPriceInfoInstance($quantityScale,$abstractProduct,$products);
            $this->priceInfos[$pId][$quantityScale]=$priceInfo;
        }
        return $this->priceInfos[$pId][$quantityScale];
    }

    public function filterProductIds($productIds, $fromPrice, $toPrice, $order, $offset, $limit) {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__METHOD__  . " is not supported for " . get_class($this));
    }

}
