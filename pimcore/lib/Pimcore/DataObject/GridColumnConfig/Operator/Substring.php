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

class Substring extends AbstractOperator
{
    private $start;

    private $length;

    private $ellipses;

    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);

        $this->label = $config->cssClass;
        $this->start = $config->start;
        $this->length = $config->length;
        $this->ellipses  = $config->ellipses;
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();

        if (!$childs) {
            return $result;
        } else {
            $c = $childs[0];

            $valueArray = [];

            $childResult = $c->getLabeledValue($element);
            $isArrayType = $childResult->isArrayType;
            $childValues = $childResult->value;
            if ($childValues && !$isArrayType) {
                $childValues = [$childValues];
            }

            /** @var $childValue string */
            if (is_array($childValues)) {
                foreach ($childValues as $childValue) {
                    $showEllipses = false;
                    if ($childValue && $this->getEllipses()) {
                        $start = $this->getStart() ? $this->getStart() : 0;
                        $length = $this->getLength() ? $this->getLength() : 0;
                        if (strlen($childValue) > $start + $length) {
                            $showEllipses = true;
                        }
                    }

                    $childValue = substr($childValue, $this->getStart(), $this->getLength());
                    if ($showEllipses) {
                        $childValue .= '...';
                    }

                    $valueArray[] = $childValue;
                }
            } else {
                $valueArray[] = $childResult->value;
            }

            $result->isArrayType = $isArrayType;
            if ($isArrayType) {
                $result->value = $valueArray;
            } else {
                $result->value = $valueArray[0];
            }
            $result->$valueArray;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param mixed $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return mixed
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param mixed $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return mixed
     */
    public function getEllipses()
    {
        return $this->ellipses;
    }

    /**
     * @param mixed $ellipses
     */
    public function setEllipses($ellipses)
    {
        $this->ellipses = $ellipses;
    }
}
