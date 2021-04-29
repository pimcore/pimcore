<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\GridColumnConfig\Operator;

/**
 * @internal
 */
final class StringReplace extends AbstractOperator
{
    /**
     * @var string
     */
    private $search;

    /**
     * @var string
     */
    private $replace;

    /**
     * @var bool
     */
    private $insensitive;

    /**
     * {@inheritdoc}
     */
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->search = $config->search ?? '';
        $this->replace = $config->replace ?? '';
        $this->insensitive = $config->insensitive ?? false;
    }

    /**
     * {@inheritdoc}
     */
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
                                $subValue = $this->replace($subValue);
                                $newSubValues[] = $subValue;
                            }
                            $newValue = $newSubValues;
                        } else {
                            $newValue = $this->replace($value);
                        }
                    }
                }

                $newChildsResult[] = $newValue;
            }

            $result->value = $newChildsResult;
        }

        return $result;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function replace($value)
    {
        if ($this->getInsensitive()) {
            return str_ireplace($this->getSearch(), $this->getReplace(), $value);
        } else {
            return str_replace($this->getSearch(), $this->getReplace(), $value);
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
     * @return string
     */
    public function getReplace()
    {
        return $this->replace;
    }

    /**
     * @param string $replace
     */
    public function setReplace($replace)
    {
        $this->replace = $replace;
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
