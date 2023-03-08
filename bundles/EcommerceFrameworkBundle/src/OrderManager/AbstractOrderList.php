<?php
declare(strict_types=1);

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
    protected int $offset = 0;

    protected int $limit = 30;

    protected int $rowCount = 0;

    protected string $listType = self::LIST_TYPE_ORDER;

    protected string $orderState = AbstractOrder::ORDER_STATE_COMMITTED;

    protected ?\ArrayIterator $list = null;

    protected string $itemClassName;

    public function getItemClassName(): string
    {
        return $this->itemClassName;
    }

    public function setItemClassName(string $className): static
    {
        $this->itemClassName = $className;

        return $this;
    }

    protected function createResultItem(array $row): OrderListItemInterface
    {
        $class = $this->getItemClassName();

        return new $class($row);
    }

    public function setListType(string $type): static
    {
        $this->listType = $type;

        return $this;
    }

    public function getListType(): string
    {
        return $this->listType;
    }

    public function getOrderState(): string
    {
        return $this->orderState;
    }

    public function setOrderState(string $orderState): static
    {
        $this->orderState = $orderState;

        return $this;
    }

    public function load(): OrderListInterface|static
    {
        if ($this->list === null) {
            // load
            $conn = \Pimcore\Db::getConnection();
            $queryBuilder = $this->getQueryBuilder();
            $this->list = new \ArrayIterator($conn->fetchAllAssociative((string) $queryBuilder, $queryBuilder->getParameters(), $queryBuilder->getParameterTypes()));
            $this->rowCount = (int)$conn->fetchOne('SELECT FOUND_ROWS()');
        }

        return $this;
    }

    /**
     * Returns a collection of items for a page.
     *
     * @param int $offset           Page offset
     * @param int $itemCountPerPage Number of items per page
     *
     * @return OrderListItemInterface[]
     */
    public function getItems(int $offset, int $itemCountPerPage): array
    {
        // load
        $this->setLimit($itemCountPerPage, $offset)->load();

        return $this->list->getArrayCopy();
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setLimit(int $limit, int $offset = 0): static
    {
        $this->limit = (int)$limit;
        $this->offset = (int)$offset;
        $this->list = null;

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return OrderListItemInterface|false
     */
    public function current(): bool|OrderListItemInterface
    {
        $this->load();
        if ($this->count() > 0) {
            return $this->createResultItem($this->list->current());
        }

        return false;
    }

    public function next(): void
    {
        $this->load();
        $this->list->next();
    }

    public function key(): int|string|null
    {
        $this->load();

        return $this->list->key();
    }

    public function valid(): bool
    {
        $this->load();

        return $this->list->valid();
    }

    public function rewind(): void
    {
        $this->load();
        $this->list->rewind();
    }

    public function seek(int $position): void
    {
        $this->load();
        $this->list->seek($position);
    }

    public function count(): int
    {
        $this->load();

        return $this->rowCount;
    }

    public function offsetExists(mixed $offset): bool
    {
        $this->load();

        return $this->list->offsetExists($offset);
    }

    public function offsetGet(mixed $offset): OrderListItemInterface
    {
        $this->load();

        return $this->createResultItem($this->list->offsetGet($offset));
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // not allowed, read only
    }

    public function offsetUnset(mixed $offset): void
    {
        // not allowed, read only
    }
}
