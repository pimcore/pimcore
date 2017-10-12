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

class Concatenator extends AbstractOperator
{
    protected $glue;
    protected $forceValue;
    protected $formatNumbers;

    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);
        $this->glue = $config->glue;
        $this->forceValue = $config->forceValue;
        $this->formatNumbers = $config->formatNumbers;
    }

    public function getLabeledValue($object)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $hasValue = true;
        if (!$this->forceValue) {
            $hasValue = false;
        }

        $childs = $this->getChilds();
        $valueArray = [];

        foreach ($childs as $c) {
            $childResult = $c->getLabeledValue($object);
            $isArrayType = $childResult->isArrayType;
            $childValues = $childResult->value;
            if ($childValues && !$isArrayType) {
                $childValues = [$childValues];
            }

            if (is_array($childValues)) {
                foreach ($childValues as $value) {
                    if (!$hasValue) {
                        if (!empty($value) || ((method_exists($value, 'isEmpty') && !$value->isEmpty()))) {
                            $hasValue = true;
                        }
                    }

                    if ($value !== null) {
                        $valueArray[] = $value;
                    }
                }
            }
        }

        if ($hasValue) {
            $result->value = implode($this->glue, $valueArray);

            return $result;
        } else {
            $result->empty = true;

            return $result;
        }
    }
}
