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

class StringContains extends AbstractOperator
{
    /** @var string */
    private $search;

    /** @var bool */
    private $insensitive;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->search = $config->search ?? '';
        $this->insensitive = $config->insensitive ?? false;
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->value = null;

        $childs = $this->getChilds();

        if ($childs) {
            $newChildsResult = [];

            foreach ($childs as $c) {
                $childResult = $c->getLabeledValue($element);
                $childValues = $childResult->value;
                if ($childValues && !is_array($childValues)) {
                    $childValues = [$childValues];
                }

                $newValue = null;

                if (is_array($childValues)) {
                    foreach ($childValues as $value) {
                        if (is_array($value)) {
                            $newSubValues = [];
                            foreach ($value as $subValue) {
                                $subValue = $this->contains($subValue);
                                $newSubValues[] = $subValue;
                            }
                            $newValue = $newSubValues;
                        } else {
                            $newValue = $this->contains($value);
                        }
                    }
                }

                $newChildsResult[] = $newValue;
            }

            if (count($childs) > 0) {
                $result->value = $newChildsResult;
            } else {
                $result->value = $newChildsResult[0];
            }
        }

        return $result;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function contains($value)
    {
        $needle = $this->getSearch();
        if (empty($needle)) {
            return false;
        }
        if ($this->getInsensitive()) {
            return stripos($value, $this->getSearch()) !== false;
        } else {
            return strpos($value, $this->getSearch()) !== false;
        }
    }

    /**
     * @return string
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * @param string $search
     */
    public function setSearch($search)
    {
        $this->search = $search;
    }

    /**
     * @return bool
     */
    public function getInsensitive()
    {
        return $this->insensitive;
    }

    /**
     * @param bool $insensitive
     */
    public function setInsensitive($insensitive)
    {
        $this->insensitive = $insensitive;
    }
}
