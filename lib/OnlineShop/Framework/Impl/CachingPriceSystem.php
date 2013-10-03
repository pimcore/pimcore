<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 11.01.12
 * Time: 11:06
 * To change this template use File | Settings | File Templates.
 */

abstract class OnlineShop_Framework_Impl_CachingPriceSystem extends OnlineShop_Framework_Impl_AbstractPriceSystem implements OnlineShop_Framework_ICachingPriceSystem {

    /** @var $priceInfos  */
    private $priceInfos = array();

    public function loadPriceInfos($productEntries, $options) {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__METHOD__  . " is not supported for " . get_class($this));
    }

    public function clearPriceInfos($productEntries, $options) {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__METHOD__  . " is not supported for " . get_class($this));
    }

    /**
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct
     * @param int | string $quantityScale
     *    quantityScale - numeric or string (allowed values: OnlineShop_Framework_IPriceInfo::MIN_PRICE
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable[] $products
     * @return OnlineShop_Framework_AbstractPriceInfo
     */
    public function getPriceInfo(OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct, $quantityScale = 1, $products = null) {
//        if (!(is_numeric($quantityScale)&&$quantityScale>=0)||!in_array($quantityScale,array(OnlineShop_Framework_IPriceInfo::MIN_PRICE))){
//            return $this->initPriceInfoInstance($quantityScale,$abstractProduct,$products);
//        //    throw new OnlineShop_Framework_Exception_UnsupportedException(__METHOD__  . " not supports quantity =  " . $quantityScale ."!");
//
//        }
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


    /**
     * @param $productIds
     * @param $fromPrice
     * @param $toPrice
     * @param $order
     * @param $offset
     * @param $limit
     *
     * @return array|void
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function filterProductIds($productIds, $fromPrice, $toPrice, $order, $offset, $limit) {
        throw new OnlineShop_Framework_Exception_UnsupportedException(__METHOD__  . " is not supported for " . get_class($this));
    }

}
