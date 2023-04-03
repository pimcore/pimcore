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
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final class ObjectFieldGetter extends AbstractOperator
{
    private string $attribute;

    private string $forwardAttribute;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->attribute = $config->attribute ?? '';
        $this->forwardAttribute = $config->forwardAttribute ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        $getter = 'get' . ucfirst($this->attribute);

        if (!$children) {
            if ($this->attribute && method_exists($element, $getter)) {
                $result->value = $element->$getter();
                if ($result->value instanceof ElementInterface) {
                    $result->value = $result->value->getFullPath();
                }

                return $result;
            }
        } else {
            $c = $children[0];
            $forwardObject = $element;

            if ($this->forwardAttribute) {
                $forwardGetter = 'get' . ucfirst($this->forwardAttribute);
                if (method_exists($element, $forwardGetter)) {
                    $forwardObject = $element->$forwardGetter();
                    if (!$forwardObject) {
                        return $result;
                    }
                } else {
                    return $result;
                }
            }

            $valueContainer = $c->getLabeledValue($forwardObject);
            $value = $valueContainer->value ?? null;
            $result->value = $value;

            if (is_array($value)) {
                $newValues = [];
                foreach ($value as $o) {
                    if ($o instanceof Concrete) {
                        if ($this->attribute && method_exists($o, $getter)) {
                            $targetValue = $o->$getter();
                            if (is_array($targetValue)) {
                                $newValues = array_merge($newValues, $targetValue);
                            } else {
                                $newValues[] = $targetValue;
                            }
                        }
                    }
                }
                $result->value = $newValues;
                $result->isArrayType = true;
            } elseif ($value instanceof Concrete) {
                $o = $value;
                if ($this->attribute && method_exists($o, $getter)) {
                    $value = $o->$getter();
                    $result->value = $value;
                }
            }
        }

        return $result;
    }
}
