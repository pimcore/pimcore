<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 13:22
 * To change this template use File | Settings | File Templates.
 */

/**
 * add a gift product to the given cart
 */
interface OnlineShop_Framework_Pricing_Action_IGift extends OnlineShop_Framework_Pricing_IAction
{
    /**
     * set gift product
     * @param OnlineShop_Framework_AbstractProduct $product
     *
     * @return OnlineShop_Framework_Pricing_Action_IGift
     */
    public function setProduct(OnlineShop_Framework_AbstractProduct $product);

    /**
     * @return OnlineShop_Framework_AbstractProduct
     */
    public function getProduct();
}