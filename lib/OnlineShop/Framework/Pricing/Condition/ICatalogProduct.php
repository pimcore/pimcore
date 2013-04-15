<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 13:16
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_Pricing_Condition_ICatalogProduct extends OnlineShop_Framework_Pricing_ICondition
{
    /**
     * @param OnlineShop_Framework_AbstractProduct $product
     *
     * @return OnlineShop_Framework_Impl_Pricing_Condition_CatalogProduct
     */
    public function setProduct(OnlineShop_Framework_AbstractProduct $product);

    /**
     * @return OnlineShop_Framework_AbstractProduct
     */
    public function getProduct();
}