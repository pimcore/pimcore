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

use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\ResultContainer;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final class Boolean extends AbstractOperator
{
    private bool $skipNull;

    private string $operator;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->skipNull = $config->skipNull ?? false;
        $this->operator = $config->operator ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        if ($this->getOperator() !== 'and' && $this->getOperator() !== 'or') {
            return $result;
        }

        if (!$children) {
            return $result;
        } else {
            $valueArray = [];
            foreach ($children as $c) {
                $childResult = $c->getLabeledValue($element);
                $isArrayType = $childResult->isArrayType ?? false;
                $childValues = $childResult->value ?? null;
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
                if ($this->getOperator() === 'and') {
                    $resultValue = $val && $resultValue;
                } else {
                    $resultValue = $val || $resultValue;
                }
            }
            $result->value = $resultValue;
        }

        return $result;
    }

    public function getSkipNull(): bool
    {
        return $this->skipNull;
    }

    public function setSkipNull(bool $skipNull): void
    {
        $this->skipNull = $skipNull;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): void
    {
        $this->operator = $operator;
    }
}
