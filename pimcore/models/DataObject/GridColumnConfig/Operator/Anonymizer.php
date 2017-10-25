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

namespace Pimcore\Model\DataObject\GridColumnConfig\Operator;

class Anonymizer extends AbstractOperator
{
    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);
        $this->mode = $config->mode;
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->isArrayType = true;

        $childs = $this->getChilds();
        $resultItems = [];

        foreach ($childs as $c) {
            $childResult = $c->getLabeledValue($element);
            $childValues = $childResult->value;

            if ($childValues) {
                if ($this->mode == 'md5') {
                    $childValues = md5($childValues);
                } elseif ($this->mode == 'bcrypt') {
                    $childValues = password_hash($childValues, PASSWORD_BCRYPT);
                }
                $resultItems[] = $childValues;
            } else {
                $resultItems[] = null;
            }
        }

        if (count($childs) == 1) {
            $result->value = $resultItems[0];
        } else {
            $result->value = $resultItems;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getFlatten()
    {
        return $this->flatten;
    }

    /**
     * @param mixed $flatten
     */
    public function setFlatten($flatten)
    {
        $this->flatten = $flatten;
    }

    /**
     * @return mixed
     */
    public function getUnique()
    {
        return $this->unique;
    }

    /**
     * @param mixed $unique
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
    }
}
