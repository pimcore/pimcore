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

use Pimcore\Model\AbstractModel;

class AnyGetter extends AbstractOperator
{
    /** @var string */
    private $attribute;

    /** @var string */
    private $param1;

    /** @var bool */
    private $isArrayType;

    /** @var string */
    private $forwardAttribute;

    /** @var string */
    private $forwardParam1;

    /** @var bool */
    private $returnLastResult;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->attribute = $config->attribute ?? '';
        $this->param1 = $config->param1 ?? '';
        $this->isArrayType = $config->isArrayType ?? false;

        $this->forwardAttribute = $config->forwardAttribute ?? '';
        $this->forwardParam1 = $config->forwardParam1 ?? '';

        $this->returnLastResult = $config->returnLastResult ?? false;
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();

        $getter = 'get'.ucfirst($this->attribute);
        $fallbackGetter = $this->attribute;

        if (!$childs) {
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
                    $forwardGetter = 'get'.ucfirst($this->forwardAttribute);
                    $forwardParam = $this->getForwardParam1();
                    if ($this->forwardAttribute && method_exists($element, $forwardGetter)) {
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
                            if ($this->attribute && method_exists($o, $getter)) {
                                $subValues[] = $o->$getter($this->getParam1());
                            } elseif ($this->attribute && method_exists($o, $fallbackGetter)) {
                                $subValues[] = $o->$fallbackGetter($this->getParam1());
                            }
                        }
                        $resultElementValue = $subValues;
                    }
                } else {
                    $o = $value;
                    if ($this->attribute && method_exists($o, $getter)) {
                        $resultElementValue = $o->$getter($this->getParam1());
                    } elseif ($this->attribute && method_exists($o, $fallbackGetter)) {
                        $resultElementValue = $o->$fallbackGetter($this->getParam1());
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
     * @return string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @param string $attribute
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * @return string
     */
    public function getParam1()
    {
        return $this->param1;
    }

    /**
     * @param string $param1
     */
    public function setParam1($param1)
    {
        $this->param1 = $param1;
    }

    /**
     * @return string
     */
    public function getForwardAttribute()
    {
        return $this->forwardAttribute;
    }

    /**
     * @param string $forwardAttribute
     */
    public function setForwardAttribute($forwardAttribute)
    {
        $this->forwardAttribute = $forwardAttribute;
    }

    /**
     * @return string
     */
    public function getForwardParam1()
    {
        return $this->forwardParam1;
    }

    /**
     * @param string $forwardParam1
     */
    public function setForwardParam1($forwardParam1)
    {
        $this->forwardParam1 = $forwardParam1;
    }

    /**
     * @return bool
     */
    public function getIsArrayType()
    {
        return $this->isArrayType;
    }

    /**
     * @param bool $isArrayType
     */
    public function setIsArrayType($isArrayType)
    {
        $this->isArrayType = $isArrayType;
    }

    /**
     * @return bool
     */
    public function getReturnLastResult()
    {
        return $this->returnLastResult;
    }

    /**
     * @param bool $returnLastResult
     */
    public function setReturnLastResult($returnLastResult)
    {
        $this->returnLastResult = $returnLastResult;
    }
}
