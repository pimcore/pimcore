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
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;

class Bracket implements BracketInterface
{
    /**
     * @var array|ConditionInterface
     */
    protected $conditions = [];

    /**
     * @var array|BracketInterface::OPERATOR_*
     */
    protected $operator = [];

    /**
     * @param ConditionInterface $condition
     * @param string $operator BracketInterface::OPERATOR_*
     *
     * @return BracketInterface
     */
    public function addCondition(ConditionInterface $condition, $operator)
    {
        $this->conditions[] = $condition;
        $this->operator[] = $operator;

        return $this;
    }

    /**
     * @param EnvironmentInterface $environment
     *
     * @return bool
     */
    public function check(EnvironmentInterface $environment)
    {
        // A bracket without conditions is not restricted and thus doesn't fail
        if (empty($this->conditions)) {
            return true;
        }

        // default
        $state = null;

        // check all conditions
        foreach ($this->conditions as $num => $condition) {
            /* @var ConditionInterface $condition */

            //The first condition shouldn't have an operator.
            //https://github.com/pimcore/pimcore/pull/7902
            $operator = $this->operator[$num];
            if ($num === 0) {
                $operator = null;
            }

            // test condition
            $check = $condition->check($environment);

            // check
            switch ($operator) {
                // first condition
                case null:
                    $state = $check;
                    break;

                // AND
                case BracketInterface::OPERATOR_AND:
                    if ($check === false) {
                        return false;
                    } else {
                        //consider current state with check, if not default.
                        $state = ($state === null) ? $check : ($check && $state);
                    }
                    break;

                // AND FALSE
                case BracketInterface::OPERATOR_AND_NOT:
                    if ($check === true) {
                        return false;
                    } else {
                        //consider current state with check, if not default.
                        $state = ($state === null) ? !$check : (!$check && $state);
                    }
                    break;

                // OR
                case BracketInterface::OPERATOR_OR:
                    if ($check === true) {
                        $state = $check;
                    }
                    break;
            }
        }

        return $state ?? false;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        $json = ['type' => 'Bracket', 'conditions' => []];
        foreach ($this->conditions as $num => $condition) {
            if ($condition) {
                /* @var ConditionInterface $condition */
                $cond = [
                    'operator' => $this->operator[$num],
                    'condition' => json_decode($condition->toJSON()),
                ];
                $json['conditions'][] = $cond;
            }
        }

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @throws \Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException
     *
     * @return $this
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

    /**
     * @param string $typeClass
     *
     * @return ConditionInterface[]
     */
    public function getConditionsByType(string $typeClass): array
    {
        $conditions = [];

        foreach ($this->conditions as $condition) {
            if ($condition instanceof BracketInterface) {
                $conditions = array_merge($condition->getConditionsByType($typeClass));
            } elseif ($condition instanceof $typeClass) {
                $conditions[] = $condition;
            }
        }

        return $conditions;
    }
}
