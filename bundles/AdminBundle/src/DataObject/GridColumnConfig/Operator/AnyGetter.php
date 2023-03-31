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
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tool\Admin;

/**
 * @internal
 */
final class AnyGetter extends AbstractOperator
{
    private string $attribute;

    private string $param1;

    private bool $isArrayType;

    private string $forwardAttribute;

    private string $forwardParam1;

    private bool $returnLastResult;

    public function __construct(\stdClass $config, array $context = [])
    {
        if (!Admin::getCurrentUser()->isAdmin()) {
            throw new \Exception('AnyGetter only allowed for admin users');
        }

        parent::__construct($config, $context);

        $this->attribute = $config->attribute ?? '';
        $this->param1 = $config->param1 ?? '';
        $this->isArrayType = $config->isArrayType ?? false;

        $this->forwardAttribute = $config->forwardAttribute ?? '';
        $this->forwardParam1 = $config->forwardParam1 ?? '';

        $this->returnLastResult = $config->returnLastResult ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        $getter = 'get'.ucfirst($this->attribute);
        $fallbackGetter = $this->attribute;

        if (!$children) {
            $result->value = null;
            if ($this->attribute && method_exists($element, $getter)) {
                $result->value = $element->$getter($this->getParam1());
            } elseif ($this->attribute && method_exists($element, $fallbackGetter)) {
                $result->value = $element->$fallbackGetter($this->getParam1());
            }

            if ($result->value instanceof AbstractModel) {
                $result->value = $result->value->getObjectVars();
            }
        } else {
            if (count($children) > 1) {
                $result->isArrayType = true;
            }
            $resultElements = [];

            if (!is_array($children)) {
                $children = [$children];
            }

            foreach ($children as $c) {
                $forwardObject = $element;

                if ($this->forwardAttribute) {
                    $forwardGetter = 'get'.ucfirst($this->forwardAttribute);
                    $forwardParam = $this->getForwardParam1();
                    if (method_exists($element, $forwardGetter)) {
                        $forwardObject = $element->$forwardGetter($forwardParam);
                        if (!$forwardObject) {
                            return $result;
                        }
                    } else {
                        return $result;
                    }
                }

                $valueContainer = $c->getLabeledValue($forwardObject);

                $value = $valueContainer->value;
                if ($value || $this->getReturnLastResult()) {
                    $resultElementValue = $value;
                } else {
                    $resultElementValue = null;
                }

                if ($this->getisArrayType()) {
                    if (is_array($value)) {
                        $subValues = [];
                        foreach ($value as $o) {
                            if ($o) {
                                if ($this->attribute && method_exists($o, $getter)) {
                                    $subValues[] = $o->$getter($this->getParam1());
                                } elseif ($this->attribute && method_exists($o, $fallbackGetter)) {
                                    $subValues[] = $o->$fallbackGetter($this->getParam1());
                                }
                            }
                        }
                        $resultElementValue = $subValues;
                    }
                } else {
                    $o = $value;
                    if ($o) {
                        if ($this->attribute && method_exists($o, $getter)) {
                            $resultElementValue = $o->$getter($this->getParam1());
                        } elseif ($this->attribute && method_exists($o, $fallbackGetter)) {
                            $resultElementValue = $o->$fallbackGetter($this->getParam1());
                        }
                    }
                }
                $resultElements[] = $resultElementValue;
            }
            if (count($children) == 1) {
                $result->value = $resultElements[0];
            } else {
                $result->value = $resultElements;
            }
        }

        return $result;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function setAttribute(string $attribute): void
    {
        $this->attribute = $attribute;
    }

    public function getParam1(): string
    {
        return $this->param1;
    }

    public function setParam1(string $param1): void
    {
        $this->param1 = $param1;
    }

    public function getForwardAttribute(): string
    {
        return $this->forwardAttribute;
    }

    public function setForwardAttribute(string $forwardAttribute): void
    {
        $this->forwardAttribute = $forwardAttribute;
    }

    public function getForwardParam1(): string
    {
        return $this->forwardParam1;
    }

    public function setForwardParam1(string $forwardParam1): void
    {
        $this->forwardParam1 = $forwardParam1;
    }

    public function getIsArrayType(): bool
    {
        return $this->isArrayType;
    }

    public function setIsArrayType(bool $isArrayType): void
    {
        $this->isArrayType = $isArrayType;
    }

    public function getReturnLastResult(): bool
    {
        return $this->returnLastResult;
    }

    public function setReturnLastResult(bool $returnLastResult): void
    {
        $this->returnLastResult = $returnLastResult;
    }
}
