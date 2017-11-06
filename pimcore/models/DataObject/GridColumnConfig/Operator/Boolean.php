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
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\GridColumnConfig\Operator;

class Boolean extends AbstractOperator
{
    private $skipNull;

    private $operator;

    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);

        $this->skipNull = $config->skipNull;
        $this->operator = $config->operator;
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();

        if ($this->getOperator() != 'and' && $this->getOperator() != 'or') {
            return $result;
        }

        if (!$childs) {
            return $result;
        } else {
            $valueArray = [];
            foreach ($childs as $c) {
                $childResult = $c->getLabeledValue($element);
                $isArrayType = $childResult->isArrayType;
                $childValues = $childResult->value;
                if ($childValues && !$isArrayType) {
                    $childValues = [$childValues];
                }

                if (is_array($childValues)) {
                    foreach ($childValues as $value) {
                        if (is_null($value) && $this->skipNull) {
                            continue;
                        }
                        $valueArray[] = $value;
                    }
                } else {
                    if (!$this->skipNull) {
                        $valueArray[] = null;
                    }
                }
            }

            $resultValue = current($valueArray);
            foreach ($valueArray as $val) {
                if ($this->getOperator() == 'and') {
                    $resultValue = $val && $resultValue;
                } elseif ($this->getOperator() == 'or') {
                    $resultValue = $val || $resultValue;
                }
            }
            $result->value = $resultValue;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getSkipNull()
    {
        return $this->skipNull;
    }

    /**
     * @param mixed $skipNull
     */
    public function setSkipNull($skipNull)
    {
        $this->skipNull = $skipNull;
    }

    /**
     * @return mixed
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param mixed $operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }
}
