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
            $isArrayType = null;

            foreach ($childs as $c) {
                $childResult = $c->getLabeledValue($element);
                $isArrayType = $childResult->isArrayType ?? false;

                $childValues = $childResult->value ?? null;
                if ($childValues && !is_array($childValues)) {
                    $childValues = [$childValues];
                }

                $newValue = null;

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
        $timestamp = null;
        if (is_int($theValue)) {
            $theValue = Carbon::createFromTimestamp($theValue);
        }
        if ($theValue instanceof Carbon) {
            $timestamp = $theValue->getTimestamp();
        }

        if ($timestamp && $this->format) {
            return date($this->format, $timestamp);
        } elseif ($theValue instanceof Carbon) {
            return $theValue->toDateString();
        }

        return $theValue;
    }
}
