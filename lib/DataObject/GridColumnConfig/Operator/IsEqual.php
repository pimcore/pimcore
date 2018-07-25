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

namespace Pimcore\DataObject\GridColumnConfig\Operator;

class IsEqual extends AbstractOperator
{
    private $capitalization;
    private $skipNull;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->capitalization = $config->capitalization;
        $this->skipNull = $config->skipNull;
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();

        if (!$childs) {
            return $result;
        } else {
            $isEqual = true;
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

            $firstValue = current($valueArray);
            foreach ($valueArray as $val) {
                if ($firstValue !== $val) {
                    $isEqual = false;
                    break;
                }
            }
            $result->value = $isEqual;
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
}
