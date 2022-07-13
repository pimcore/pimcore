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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;

abstract class AbstractOrderList implements OrderListInterface
{
    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var int
     */
    protected $limit = 30;

    /**
     * @var int
     */
    protected $rowCount = 0;

    /**
     * @var string
     */
    protected $listType = self::LIST_TYPE_ORDER;

    /**
     * @var string
     */
    protected $orderState = AbstractOrder::ORDER_STATE_COMMITTED;

    /**
     * @var \ArrayIterator|null
     */
    protected $list;

    /**
     * @var string
     */
    protected $itemClassName;

    /**
     * @return string
     */
    public function getItemClassName()
    {
        return $this->itemClassName;
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public function setItemClassName($className)
    {
        $this->itemClassName = $className;

        return $this;
    }

    /**
     * @param array $row
     *
     * @return OrderListItemInterface
     */
    protected function createResultItem(array $row)
    {
        $class = $this->getItemClassName();

        return new $class($row);
    }

    /**
     * @param string $type
     *
     * @return OrderListInterface
     */
    public function setListType($type)
    {
        $this->listType = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getListType()
    {
        return $this->listType;
    }

    /**
     * @return string
     */
    public function getOrderState()
    {
        return $this->orderState;
    }

    /**
     * @param string $orderState
     *
     * @return $this
     */
    public function setOrderState($orderState)
    {
        $this->orderState = $orderState;

        return $this;
    }

    /** @inheritDoc */
    public function load()
    {
        if ($this->list === null) {
            // load
            $conn = \Pimcore\Db::getConnection();
            $queryBuilder = $this->getQueryBuilder();
            $this->list = new \ArrayIterator($conn->fetchAllAssociative((string) $queryBuilder, $queryBuilder->getParameters(), $queryBuilder->getParameterTypes()));
            $this->rowCount = $this->list->count();
        }

        return $this;
    }

    /**
     * Returns a collection of items for a page.
     *
     * @param  int $offset           Page offset
     * @param  int $itemCountPerPage Number of items per page
     *
     * @return OrderListItemInterface[]
     */
    public function getItems($offset, $itemCountPerPage)
    {
        // load
        $this->setLimit($itemCountPerPage, $offset)->load();

        return $this->list->getArrayCopy();
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return $this
     */
    public function setLimit($limit, $offset = 0)
    {
        $this->limit = (int)$limit;
        $this->offset = (int)$offset;
        $this->list = null;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return OrderListItemInterface|false
     */
    #[\ReturnTypeWillChange]
    public function current()// : OrderListItemInterface|false
    {
        $this->load();
        if ($this->count() > 0) {
            return $this->createResultItem($this->list->current());
        }

        return false;
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function next()// : void
    {
        $this->load();
        $this->list->next();
    }

    /**
     * @return string|int|null
     */
    #[\ReturnTypeWillChange]
    public function key()// : string|int|null
    {
        $this->load();

        return $this->list->key();
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()// : bool
    {
        $this->load();

        return $this->list->valid();
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function rewind()// : void
    {
        $this->load();
        $this->list->rewind();
    }

    /**
     * @param int $position
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function seek($position)// : void
    {
        $this->load();
        $this->list->seek($position);
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()// : int
    {
        $this->load();

        return $this->rowCount;
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)// : bool
    {
        $this->load();

        return $this->list->offsetExists($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return OrderListItemInterface
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)// : OrderListItemInterface
    {
        $this->load();

        return $this->createResultItem($this->list->offsetGet($offset));
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)// : void
    {
        // not allowed, read only
    }

    /**
     * @param mixed $offset
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)// : void
    {
        // not allowed, read only
    }
}
