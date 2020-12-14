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
    /** @var int */
    private $start;

    /** @var int */
    private $length;

    /** @var bool */
    private $ellipses;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->start = $config->start ?? 0;
        $this->length = $config->length ?? 0;
        $this->ellipses = $config->ellipses ?? false;
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
            $isArrayType = $childResult->isArrayType ?? false;
            $childValues = $childResult->value ?? null;
            if ($childValues && !$isArrayType) {
                $childValues = [$childValues];
            }

            /** @var array $childValues */
            if (is_array($childValues)) {
                /** @var string $childValue */
                foreach ($childValues as $childValue) {
                    $showEllipses = false;
                    if ($childValue && $this->getEllipses()) {
                        $start = $this->getStart() ? $this->getStart() : 0;
                        $length = $this->getLength() ? $this->getLength() : 0;
                        if (strlen($childValue) > ($start + $length)) {
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
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param int $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return bool
     */
    public function getEllipses()
    {
        return $this->ellipses;
    }

    /**
     * @param bool $ellipses
     */
    public function setEllipses($ellipses)
    {
        $this->ellipses = $ellipses;
    }
}
