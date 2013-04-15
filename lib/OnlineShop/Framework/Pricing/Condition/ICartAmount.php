<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 10.04.13
 * Time: 13:50
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_Pricing_Condition_ICartAmount extends OnlineShop_Framework_Pricing_ICondition
{
    /**
     * @param float $limit
     *
     * @return OnlineShop_Framework_Pricing_Condition_ICartAmount
     */
    public function setLimit($limit);

    /**
     * @return float
     */
    public function getLimit();
}