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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Listing;

use Pimcore\Db;
use Pimcore\Model\AbstractModel;

/**
 * Class AbstractListing
 *
 * @package Pimcore\Model\Listing
 *
 * @method \Pimcore\Db\ZendCompatibility\QueryBuilder getQuery()
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
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var string
     */
    protected $condition;

    /**
     * @var array
     */
    protected $conditionVariables = [];

    /**
     * @var array
     */
    protected $conditionVariablesFromSetCondition;

    /**
     * @var string
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
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
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

        if (intval($limit) > 0) {
            $this->limit = intval($limit);
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

        if (intval($offset) > 0) {
            $this->offset = intval($offset);
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
                    $this->orderKey[] = '`' . $o . '`';
                }
            }
        }

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string $concatenator
     *
     * @return $this
     */
    public function addConditionParam($key, $value = null, $concatenator = 'AND')
    {
        $this->setData(null);

        $key = '('.$key.')';
        $ignore = true;
        if (strpos($key, '?') !== false || strpos($key, ':') !== false) {
            $ignore = false;
        }
        $this->conditionParams[$key] = [
            'value' => $value,
            'concatenator' => $concatenator,
            'ignore-value' => $ignore, // If there is not a placeholder, ignore value!
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
        $db = \Pimcore\Db::get();

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
                    $conditionVariableTypes[$pkey] = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
                } else {
                    $conditionVariableTypes[$pkey] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
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

        $condition = $this->condition . $conditionString;

        return $condition;
    }

    /**
     * @param string $condition
     * @param array|null $conditionVariables
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
     * @return string
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

            if ($qoute && strpos($groupBy, '`') !== 0) {
                $this->groupBy = '`' . $this->groupBy . '`';
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
        $db = Db::get();

        return $db->escapeLike($value);
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
        $this->getCondition();          // this will merge conditionVariablesFromSetCondition and additional params into conditionVariables
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
     * @return array
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
            $dao = $this->getDao();
            if (\method_exists($dao, 'load')) {
                $this->getDao()->load();
            } else {
                @trigger_error(
                    'Please provide load() method in '.\get_class($dao).'. This method will be required in Pimcore 7.',
                    \E_USER_DEPRECATED
                );
            }
        }

        return $this->data;
    }

    /**
     * @param array|null $data
     *
     * @return static
     */
    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $this->getData();

        return current($this->data);
    }

    /**
     * @return mixed
     */
    public function key()
    {
        $this->getData();

        return key($this->data);
    }

    /**
     * @return mixed|null
     */
    public function next()
    {
        $this->getData();

        return next($this->data);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        $this->getData();

        return $this->current() !== false;
    }

    public function rewind()
    {
        $this->getData();
        reset($this->data);
    }

    /**
     * @return int
     */
    public function count()
    {
        $dao = $this->getDao();
        if (!\method_exists($dao, 'getTotalCount')) {
            @trigger_error('Listings should implement Countable interface', E_USER_DEPRECATED);

            return 0;
        }

        return $dao->getTotalCount();
    }
}
