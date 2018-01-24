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

use Carbon\Carbon;

class DateFormatter extends AbstractOperator
{
    /**
     * @var string|null
     */
    private $format;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->format = ($config->format ? $config->format : null);
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->value = null;

        $childs = $this->getChilds();

        if ($childs) {
            $newChildsResult = [];

            foreach ($childs as $c) {
                $childResult = $c->getLabeledValue($element);
                $isArrayType = $childResult->isArrayType;

                $childValues = $childResult->value;
                if ($childValues && !is_array($childValues)) {
                    $childValues = [$childValues];
                }

                if (is_array($childValues)) {
                    foreach ($childValues as $value) {
                        if (is_array($value)) {
                            $newSubValues = [];
                            foreach ($value as $subValue) {
                                $subValue = $this->format($subValue);
                                $newSubValues[] = $subValue;
                            }
                            $newValue = $newSubValues;
                        } else {
                            $newValue = $this->format($value);
                        }
                    }
                } else {
                    $newValue = null;
                }

                $newChildsResult[] = $newValue;
            }

            $result->isArrayType = $isArrayType;
            if ($isArrayType) {
                $result->value = $newChildsResult;
            } else {
                $result->value = $newChildsResult[0];
            }
        }

        return $result;
    }

    public function format($theValue)
    {
        if ($theValue) {
            if (is_integer($theValue)) {
                $theValue = Carbon::createFromTimestamp($theValue);
            }
            if ($this->format) {
                $timestamp = null;

                if ($theValue instanceof Carbon) {
                    $timestamp = $theValue->getTimestamp();

                    $theValue = date($this->format, $timestamp);
                }
            } else {
                if ($theValue instanceof Carbon) {
                    $theValue = $theValue->toDateString();
                }
            }
        }

        return $theValue;
    }
}
