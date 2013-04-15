<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 13:14
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_Pricing_Condition_IDateRange extends OnlineShop_Framework_Pricing_ICondition
{
    /**
     * @param Zend_Date $date
     *
     * @return OnlineShop_Framework_Pricing_Condition_IDateRange
     */
    public function setStarting(Zend_Date $date);

    /**
     * @param Zend_Date $date
     *
     * @return OnlineShop_Framework_Pricing_Condition_IDateRange
     */
    public function setEnding(Zend_Date $date);

    /**
     * @return Zend_Date
     */
    public function getStarting();

    /**
     * @return Zend_Date
     */
    public function getEnding();
}