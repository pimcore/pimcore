<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 13:17
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_Pricing_Condition_IBracket extends OnlineShop_Framework_Pricing_ICondition
{
    const OPERATOR_AND = 'and';
    const OPERATOR_OR = 'or';
    const OPERATOR_AND_NOT = 'and_not';

    /**
     * @param OnlineShop_Framework_Pricing_ICondition $condition
     * @param string $operator OnlineShop_Framework_Pricing_Condition_IBracket::OPERATOR_*
     *
     * @return OnlineShop_Framework_Pricing_Condition_IBracket
     */
    public function addCondition(OnlineShop_Framework_Pricing_ICondition $condition, $operator);
}