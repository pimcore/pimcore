<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 13:16
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_Pricing_Condition_ICategory extends OnlineShop_Framework_Pricing_ICondition
{
    /**
     * @param OnlineShop_Framework_AbstractCategory[] $categories
     *
     * @return OnlineShop_Framework_Pricing_Condition_ICategory
     */
    public function setCategories(array $categories);

    /**
     * @return OnlineShop_Framework_AbstractCategory[]
     */
    public function getCategories();
}