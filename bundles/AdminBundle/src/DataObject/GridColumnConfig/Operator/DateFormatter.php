<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator;

use Carbon\Carbon;
use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\ResultContainer;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final class DateFormatter extends AbstractOperator
{
    private ?string $format = null;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->format = ($config->format ? $config->format : null);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->value = null;

        $children = $this->getChildren();

        if ($children) {
            $newChildrenResult = [];
            $isArrayType = null;

            foreach ($children as $c) {
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

                $newChildrenResult[] = $newValue;
            }

            $result->isArrayType = $isArrayType;
            if ($isArrayType) {
                $result->value = $newChildrenResult;
            } else {
                $result->value = $newChildrenResult[0];
            }
        }

        return $result;
    }

    public function format(mixed $theValue): string
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
