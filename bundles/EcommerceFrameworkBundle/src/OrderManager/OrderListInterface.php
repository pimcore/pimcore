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

use ArrayAccess;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Model\Paginator\PaginateListingInterface;
use SeekableIterator;

/**
 * Interface OrderListInterface
 *
 * @method OrderListItemInterface|false current()
 */
interface OrderListInterface extends SeekableIterator, ArrayAccess, PaginateListingInterface
{
    const LIST_TYPE_ORDER = 'order';

    const LIST_TYPE_ORDER_ITEM = 'item';

    public function getQueryBuilder(): DoctrineQueryBuilder;

    public function load(): OrderListInterface;

    public function setLimit(int $limit, int $offset = 0): OrderListInterface;

    public function getLimit(): int;

    public function getOffset(): int;

    /**
     * @return $this
     */
    public function setOrder(string $order): static;

    /**
     * @return $this
     */
    public function setOrderState(string $state): static;

    public function getOrderState(): string;

    /**
     * @return $this
     */
    public function setListType(string $type): static;

    public function getListType(): string;

    public function getItemClassName(): string;

    /**
     * @return $this
     */
    public function setItemClassName(string $className): static;

    /**
     * enable payment info query
     * table alias: paymentInfo
     *
     * @return $this
     */
    public function joinPaymentInfo(): static;

    /**
     * enable order item objects query
     * table alias: orderItemObjects
     *
     * @return $this
     */
    public function joinOrderItemObjects(): static;

    /**
     * enable product query
     * table alias: product
     *
     * @param string $classId
     *
     * @return $this
     */
    public function joinProduct(string $classId): static;

    /**
     * enable customer query
     * table alias: customer
     *
     * @param string $classId
     *
     * @return $this
     */
    public function joinCustomer(string $classId): static;

    /**
     * enable pricing rule query
     * table alias: pricingRule
     *
     * @return $this
     */
    public function joinPricingRule(): static;

    /**
     * @param string $condition
     * @param string|null $value
     *
     * @return $this
     */
    public function addCondition(string $condition, string $value = null): static;

    /**
     * @return $this
     */
    public function addSelectField(string $field): static;

    /**
     * @return $this
     */
    public function addFilter(OrderListFilterInterface $filter): static;

    public function useSubItems(): bool;

    /**
     * @return $this
     */
    public function setUseSubItems(bool $useSubItems): static;
}
