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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Listing;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Pimcore\Db;
use Pimcore\Db\Helper;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Listing\Dao\AbstractDao;

/**
 * @method AbstractDao getDao()
 * @method QueryBuilder getQueryBuilder()
 */
abstract class AbstractListing extends AbstractModel implements \Iterator, \Countable
{
    /**
     * @var array
     */
    protected $order = [];

    /**
     * @var array
     */
    protected $orderKey = [];

    /**
     * @var int|null
     */
    protected $limit;

    /**
     * @var int|null
     */
    protected $offset;

    /**
     * @var string|null
     */
    protected $condition;

    /**
     * @var array
     */
    protected $conditionVariables = [];

    /**
     * @var array|null
     */
    protected $conditionVariablesFromSetCondition;

    /**
     * @var string|null
     */
    protected $groupBy;

    /**
     * @var array
     */
    protected $validOrders = [
        'ASC',
        'DESC',
    ];

    /**
     * @var array
     */
    protected $conditionParams = [];

    /**
     * @var array
     */
    protected $conditionVariableTypes = [];

    /**
     * @var array|null
     */
    protected $data;

    /**
     * @return array
     */
    public function getConditionVariableTypes(): array
    {
        if (!$this->conditionVariables) {
            $this->getCondition();
        }

        return $this->conditionVariableTypes;
    }

    /**
     * @param array $conditionVariableTypes
     */
    public function setConditionVariableTypes(array $conditionVariableTypes): void
    {
        $this->conditionVariableTypes = $conditionVariableTypes;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * @return int|null
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int|null
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return array
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->setData(null);

        if ((int)$limit > 0) {
            $this->limit = (int)$limit;
        }

        return $this;
    }

    /**
     * @param int $offset
     *
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->setData(null);

        if ((int)$offset >= 0) {
            $this->offset = (int)$offset;
        }

        return $this;
    }

    /**
     * @param array|string $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->setData(null);

        $this->order = [];

        if (!empty($order)) {
            if (is_string($order)) {
                $order = strtoupper($order);
                if (in_array($order, $this->validOrders)) {
                    $this->order[] = $order;
                }
            } elseif (is_array($order)) {
                foreach ($order as $o) {
                    $o = strtoupper($o);
                    if (in_array($o, $this->validOrders)) {
                        $this->order[] = $o;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getOrderKey()
    {
        return $this->orderKey;
    }

    /**
     * @param string|array $orderKey
     * @param bool $quote
     *
     * @return $this
     */
    public function setOrderKey($orderKey, $quote = true)
    {
        $this->setData(null);

        $this->orderKey = [];

        if (is_string($orderKey) && !empty($orderKey)) {
            $orderKey = [$orderKey];
        }

        if (is_array($orderKey)) {
            foreach ($orderKey as $o) {
                if ($quote === false) {
                    $this->orderKey[] = $o;
                } elseif ($this->isValidOrderKey($o)) {
                    $this->orderKey[] = $this->quoteIdentifier($o);
                }
            }
        }

        return $this;
    }

    /**
     * @param string $condition
     * @param mixed $value
     * @param string $concatenator
     *
     * @return $this
     */
    public function addConditionParam($condition, $value = null, $concatenator = 'AND')
    {
        $this->setData(null);

        $condition = '('.$condition.')';
        $ignoreParameter = true;

        $conditionWithoutQuotedStrings = preg_replace('/["\'][^"\']*?["\']/', '', $condition);
        if (str_contains($conditionWithoutQuotedStrings, '?') || str_contains($conditionWithoutQuotedStrings, ':')) {
            $ignoreParameter = false;
        }
        $this->conditionParams[$condition] = [
            'value' => $value,
            'concatenator' => $concatenator,
            'ignore-value' => $ignoreParameter, // If there is not a placeholder, ignore value!
        ];

        return $this;
    }

    /**
     * @return array
     */
    public function getConditionParams()
    {
        return $this->conditionParams;
    }

    /**
     * @return $this
     */
    public function resetConditionParams()
    {
        $this->setData(null);

        $this->conditionParams = [];

        return $this;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        $conditionString = '';
        $conditionVariableTypes = [];
        $conditionParams = $this->getConditionParams();

        $params = [];
        if (!empty($conditionParams)) {
            $i = 0;
            foreach ($conditionParams as $key => $value) {
                if (!$this->condition && $i == 0) {
                    $conditionString .= $key . ' ';
                } else {
                    $conditionString .= ' ' . $value['concatenator'] . ' ' . $key . ' ';
                }

                // If there is not a placeholder, ignore value!
                if (!$value['ignore-value']) {
                    if (is_array($value['value'])) {
                        foreach ($value['value'] as $k => $v) {
                            if (is_int($k)) {
                                $params[] = $v;
                            } else {
                                $params[$k] = $v;
                            }
                        }
                    } else {
                        $params[] = $value['value'];
                    }
                }
                $i++;
            }
        }
        $params = array_merge((array) $this->getConditionVariablesFromSetCondition(), $params);

        $this->setConditionVariables($params);

        foreach ($params as $pkey => $param) {
            if (is_array($param)) {
                if (isset($param[0]) && is_string($param[0])) {
                    $conditionVariableTypes[$pkey] = Connection::PARAM_STR_ARRAY;
                } else {
                    $conditionVariableTypes[$pkey] = Connection::PARAM_INT_ARRAY;
                }
            } else {
                if (is_bool($param)) {
                    $type = \PDO::PARAM_BOOL;
                } elseif (is_int($param)) {
                    $type = \PDO::PARAM_INT;
                } elseif (is_null($param)) {
                    $type = \PDO::PARAM_NULL;
                } else {
                    $type = \PDO::PARAM_STR;
                }

                $conditionVariableTypes[$pkey] = $type;
            }
        }

        $this->setConditionVariableTypes($conditionVariableTypes);

        return $this->condition . $conditionString;
    }

    /**
     * @param string $condition
     * @param array|scalar $conditionVariables
     *
     * @return $this
     */
    public function setCondition($condition, $conditionVariables = null)
    {
        $this->setData(null);

        $this->condition = $condition;

        // statement variables
        if (is_array($conditionVariables)) {
            $this->setConditionVariablesFromSetCondition($conditionVariables);
        } elseif ($conditionVariables !== null) {
            $this->setConditionVariablesFromSetCondition([$conditionVariables]);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * @return array
     */
    public function getValidOrders()
    {
        return $this->validOrders;
    }

    /**
     * @param string $groupBy
     * @param bool $qoute
     *
     * @return $this
     */
    public function setGroupBy($groupBy, $qoute = true)
    {
        $this->setData(null);

        if ($groupBy) {
            $this->groupBy = $groupBy;

            if ($qoute) {
                $quotedParts = [];
                $parts = explode(',', trim($groupBy, '`'));
                foreach ($parts as $part) {
                    $quotedParts[] = $this->quoteIdentifier(trim($part));
                }

                $this->groupBy = implode(', ', $quotedParts);
            }
        }

        return $this;
    }

    /**
     * @param array $validOrders
     *
     * @return $this
     */
    public function setValidOrders($validOrders)
    {
        $this->validOrders = $validOrders;

        return $this;
    }

    public function quoteIdentifier(string $value): string
    {
        $db = Db::get();

        return $db->quoteIdentifier($value);
    }

    /**
     * @param mixed $value
     * @param int|null $type
     *
     * @return string
     */
    public function quote($value, $type = null)
    {
        $db = Db::get();

        return $db->quote($value, $type);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function escapeLike(string $value): string
    {
        return Helper::escapeLike($value);
    }

    /**
     * @param array $conditionVariables
     *
     * @return $this
     */
    public function setConditionVariables($conditionVariables)
    {
        $this->conditionVariables = $conditionVariables;

        return $this;
    }

    /**
     * @return array
     */
    public function getConditionVariables()
    {
        if (!$this->conditionVariables) {
            $this->getCondition();
        }

        return $this->conditionVariables;
    }

    /**
     * @param array $conditionVariables
     *
     * @return $this
     */
    public function setConditionVariablesFromSetCondition($conditionVariables)
    {
        $this->setData(null);

        $this->conditionVariablesFromSetCondition = $conditionVariables;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getConditionVariablesFromSetCondition()
    {
        return $this->conditionVariablesFromSetCondition;
    }

    /**
     * @return bool
     */
    public function isLoaded()
    {
        return $this->data !== null;
    }

    /**
     * @return array
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->getDao()->load();
        }

        return $this->data;
    }

    /**
     * @param array|null $data
     *
     * @return $this
     */
    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()// : mixed
    {
        $this->getData();

        return current($this->data);
    }

    /**
     * @return int|string|null
     */
    #[\ReturnTypeWillChange]
    public function key()// : mixed
    {
        $this->getData();

        return key($this->data);
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function next()// : void
    {
        $this->getData();
        next($this->data);
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()// : bool
    {
        $this->getData();

        return $this->current() !== false;
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function rewind()// : void
    {
        $this->getData();
        reset($this->data);
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()// : int
    {
        return $this->getDao()->getTotalCount();
    }
}
