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

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;

class ObjectFieldGetter extends AbstractOperator
{
    /** @var string */
    private $attribute;

    /** @var string */
    private $forwardAttribute;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->attribute = $config->attribute ?? '';
        $this->forwardAttribute = $config->forwardAttribute ?? '';
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();

        $getter = 'get' . ucfirst($this->attribute);

        if (!$childs) {
            if ($this->attribute && method_exists($element, $getter)) {
                $result->value = $element->$getter();
                if ($result->value instanceof ElementInterface) {
                    $result->value = $result->value->getFullPath();
                }

                return $result;
            }
        } else {
            $c = $childs[0];
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
                            $newValues[] = $targetValue;
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
