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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;

class Bracket implements IBracket
{
    /**
     * @var array|\Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition
     */
    protected $conditions = [];

    /**
     * @var array|IBracket::OPERATOR_*
     */
    protected $operator = [];

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition $condition
     * @param string $operator IBracket::OPERATOR_*
     *
     * @return IBracket
     */
    public function addCondition(\Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition $condition, $operator)
    {
        $this->conditions[] = $condition;
        $this->operator[] = $operator;

        return $this;
    }

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment
     *
     * @return boolean
     */
    public function check(\Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment)
    {
        // A bracket without conditions is not restricted and thus doesn't fail
        if (sizeof($this->conditions) == 0) {
            return true;
        }

        // default
        $state = false;

        // check all conditions
        foreach ($this->conditions as $num => $condition) {
            /* @var \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition $condition */

            // test condition
            $check = $condition->check($environment);

            // check
            switch ($this->operator[$num]) {
                // first condition
                case null:
                    $state = $check;
                    break;

                // AND
                case IBracket::OPERATOR_AND:
                    if ($check === false) {
                        return false;
                    } else {
                        $state = true;
                    }
                    break;

                // AND FALSE
                case IBracket::OPERATOR_AND_NOT:
                    if ($check === true) {
                        return false;
                    } else {
                        $state = true;
                    }
                    break;

                // OR
                case IBracket::OPERATOR_OR:
                    if ($check === true) {
                        $state = $check;
                    }
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
        $json = ['type' => 'Bracket', 'conditions' => []];
        foreach ($this->conditions as $num => $condition) {
            if ($condition) {
                /* @var \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition $condition */
                $cond = [
                    'operator' => $this->operator[$num],
                    'condition' => json_decode($condition->toJSON())
                ];
                $json['conditions'][] = $cond;
            }
        }

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return $this|\Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        foreach ($json->conditions as $setting) {
            $subcond = Factory::getInstance()->getPricingManager()->getCondition($setting->type);
            $subcond->fromJSON(json_encode($setting));

            $this->addCondition($subcond, $setting->operator);
        }

        return $this;
    }
}
