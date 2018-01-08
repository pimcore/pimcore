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

class AnyGetter extends AbstractOperator
{
    private $attribute;

    private $param1;

    private $isArrayType;

    private $forwardAttribute;

    private $forwardParam1;

    private $returnLastResult;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->attribute = $config->attribute;
        $this->param1 = $config->param1;
        $this->isArrayType = $config->isArrayType;

        $this->forwardAttribute = $config->forwardAttribute;
        $this->forwardParam1 = $config->forwardParam1;

        $this->returnLastResult = $config->returnLastResult;
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();

        $getter = 'get' . ucfirst($this->attribute);

        if (!$childs) {
            if (method_exists($element, $getter)) {
                $result->value = $element->$getter();

                return $result;
            }
        } else {
            if (count($childs) > 1) {
                $result->isArrayType = true;
            }
            $resultElements = [];

            if (!is_array($childs)) {
                $childs = [$childs];
            }

            foreach ($childs as $c) {
                $forwardObject = $element;

                if ($this->forwardAttribute) {
                    $forwardGetter = 'get' . ucfirst($this->forwardAttribute);
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
                if ($this->getReturnLastResult()) {
                    $resultElementValue = $value;
                } else {
                    $resultElementValue = null;
                }

                if ($this->getisArrayType()) {
                    if (is_array($value)) {
                        $newValues = [];
                        foreach ($value as $o) {
                            if (method_exists($o, $getter)) {
                                $targetValue = $o->$getter($this->getParam1());
                                $newValues[] = $targetValue;
                            }
                        }
                        $resultElementValue = $newValues;
                    }
                } else {
                    $o = $value; // Concrete::getById($value->getId());
                    if (method_exists($o, $getter)) {
                        $value = $o->$getter($this->getParam1());
                        $resultElementValue = $value;
                    }
                }
                $resultElements[] = $resultElementValue;
            }
            if (count($childs) == 1) {
                $result->value = $resultElements[0];
            } else {
                $result->value = $resultElements;
            }
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @param mixed $attribute
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * @return mixed
     */
    public function getParam1()
    {
        return $this->param1;
    }

    /**
     * @param mixed $param1
     */
    public function setParam1($param1)
    {
        $this->param1 = $param1;
    }

    /**
     * @return mixed
     */
    public function getForwardAttribute()
    {
        return $this->forwardAttribute;
    }

    /**
     * @param mixed $forwardAttribute
     */
    public function setForwardAttribute($forwardAttribute)
    {
        $this->forwardAttribute = $forwardAttribute;
    }

    /**
     * @return mixed
     */
    public function getForwardParam1()
    {
        return $this->forwardParam1;
    }

    /**
     * @param mixed $forwardParam1
     */
    public function setForwardParam1($forwardParam1)
    {
        $this->forwardParam1 = $forwardParam1;
    }

    /**
     * @return mixed
     */
    public function getisArrayType()
    {
        return $this->isArrayType;
    }

    /**
     * @param mixed $isArrayType
     */
    public function setIsArrayType($isArrayType)
    {
        $this->isArrayType = $isArrayType;
    }

    /**
     * @return mixed
     */
    public function getReturnLastResult()
    {
        return $this->returnLastResult;
    }

    /**
     * @param mixed $returnLastResult
     */
    public function setReturnLastResult($returnLastResult)
    {
        $this->returnLastResult = $returnLastResult;
    }
}
