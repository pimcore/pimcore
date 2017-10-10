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


namespace Pimcore\Model\DataObject\GridConfig\Operator;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\GridConfig\ArrayTypeInterface;
use Pimcore\Model\Element\ElementInterface;

class ObjectFieldGetter extends AbstractOperator {

    protected $attribute;

    protected $forwardAttribute;


    public function __construct($config, $context = null) {
        parent::__construct($config, $context);
        $this->attribute = $config->attribute;
        $this->forwardAttribute = $config->forwardAttribute;
    }

    public function getLabeledValue($object) {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();

        $getter = "get" . ucfirst($this->attribute);

        if (!$childs) {
            //TODO check if this actually makes sense

            if (method_exists($object, $getter)) {
                $result->value = $object->$getter();
                if ($result->value instanceof ElementInterface) {
                    $result->value = $result->value->getFullPath();
                }
                return $result;
            }
        } else {

            $c = $childs[0];
            $forwardObject = $object;

            if ($this->forwardAttribute) {
                $forwardGetter = "get" . ucfirst($this->forwardAttribute);
                if (method_exists($object, $forwardGetter)) {
                    $forwardObject = $object->$forwardGetter();
                    if (!$forwardObject) {
                        return $result;
                    }
                } else {
                    return $result;
                }
            }

            $value = $c->getLabeledValue($forwardObject)->value;
            $result->value = $value;

            if ($c instanceof ArrayTypeInterface) {
                if (is_array($value)) {
                    $newValues = array();
                    foreach ($value as $o) {
                        if ($o instanceof Concrete) {
                            if (method_exists($o, $getter)) {
                                $targetValue = $o->$getter();
                                $newValues[] = $targetValue;
                            }
                        }

                    }
                    $result->value = $newValues;
                    $result->isArrayType = true;
                }

            } else if ($value instanceof Concrete) {
                $o = $value; // Concrete::getById($value->getId());
                if (method_exists($o, $getter)) {
                    $value = $o->$getter();
                    $result->value = $value;
                }
            }

        }
        return $result;
    }
}
