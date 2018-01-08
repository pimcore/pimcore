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

class Concatenator extends AbstractOperator
{
    private $glue;
    private $forceValue;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->glue = $config->glue;
        $this->forceValue = $config->forceValue;
    }

    public function getLabeledValue($element)
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
            $childResult = $c->getLabeledValue($element);
            $childValues = $childResult->value;
            if ($childValues && !is_array($childValues)) {
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
            } else {
                $valueArray[] = $value;
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
