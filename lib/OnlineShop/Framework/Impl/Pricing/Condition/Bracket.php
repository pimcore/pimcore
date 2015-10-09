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


class OnlineShop_Framework_Impl_Pricing_Condition_Bracket implements OnlineShop_Framework_Pricing_Condition_IBracket
{
    /**
     * @var array|OnlineShop_Framework_Pricing_ICondition
     */
    protected $conditions = array();

    /**
     * @var array|OnlineShop_Framework_Pricing_Condition_IBracket::OPERATOR_*
     */
    protected $operator = array();

    /**
     * @param OnlineShop_Framework_Pricing_ICondition $condition
     * @param string $operator OnlineShop_Framework_Pricing_Condition_IBracket::OPERATOR_*
     *
     * @return OnlineShop_Framework_IRule
     */
    public function addCondition(OnlineShop_Framework_Pricing_ICondition $condition, $operator)
    {
        $this->conditions[] = $condition;
        $this->operator[] = $operator;
        return $this;
    }

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        // default
        $state = false;

        // check all conditions
        foreach($this->conditions as $num => $condition)
        {
            /* @var OnlineShop_Framework_Pricing_ICondition $condition */

            // test condition
            $check = $condition->check($environment);

            // check
            switch($this->operator[$num])
            {
                // first condition
                case null:
                    $state = $check;
                    break;

                // AND
                case OnlineShop_Framework_Pricing_Condition_IBracket::OPERATOR_AND:
                    if($check === false)
                        return false;
                    else
                        $state = true;
                    break;

                // AND FALSE
                case OnlineShop_Framework_Pricing_Condition_IBracket::OPERATOR_AND_NOT:
                    if($check === true)
                        return false;
                    else
                        $state = true;
                    break;

                // OR
                case OnlineShop_Framework_Pricing_Condition_IBracket::OPERATOR_OR:
                    if($check === true)
                        $state = $check;
                    break;
            }
        }

        return $state;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        $json = array('type' => 'Bracket', 'conditions' => array());
        foreach($this->conditions as $num => $condition)
        {
            /* @var OnlineShop_Framework_Pricing_ICondition $condition */
            $cond = array(
                'operator' => $this->operator[$num],
                'condition' => json_decode($condition->toJSON())
            );
            $json['conditions'][] = $cond;
        }

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return $this|OnlineShop_Framework_Pricing_ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        foreach($json->conditions as $setting)
        {
            $subcond = OnlineShop_Framework_Factory::getInstance()->getPricingManager()->getCondition( $setting->type );
            $subcond->fromJSON( json_encode($setting) );

            $this->addCondition($subcond, $setting->operator);
        }

        return $this;
    }
}