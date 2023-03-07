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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Model\Paginator\PaginateListingInterface;

/**
 * Interface for product list which works based on the product index of the online shop framework
 */
interface ProductListInterface extends PaginateListingInterface
{
    const ORDERKEY_PRICE = 'orderkey_price';

    const PRODUCT_TYPE_OBJECT = 'object';

    const PRODUCT_TYPE_VARIANT = 'variant';

    /**
     * Variant mode defines how to consider variants in product list results
     * - does not consider variants in search results
     */
    const VARIANT_MODE_HIDE = 'hide';

    /**
     * Variant mode defines how to consider variants in product list results
     * - considers variants in search results and returns objects and variants
     */
    const VARIANT_MODE_INCLUDE = 'include';

    /**
     * Variant mode defines how to consider variants in product list results
     * - considers variants in search results and returns ONLY variants
     */
    const VARIANT_MODE_VARIANTS_ONLY = 'variants_only';

    /**
     * Variant mode defines how to consider variants in product list results
     * - considers variants in search results but only returns corresponding objects in search results
     */
    const VARIANT_MODE_INCLUDE_PARENT_OBJECT = 'include_parent_object';

    /**
     * Returns all products valid for this search
     *
     * @return IndexableInterface[]
     */
    public function getProducts(): array;

    /**
     * Adds filter condition to product list
     * Fieldname is optional but highly recommended - needed for resetting condition based on fieldname
     * and exclude functionality in group by results
     *
     * @param array|string $condition
     * @param string $fieldname
     */
    public function addCondition(array|string $condition, string $fieldname = ''): void;

    /**
     * Adds query condition to product list for fulltext search
     * Fieldname is optional but highly recommended - needed for resetting condition based on fieldname
     * and exclude functionality in group by results
     *
     * @param string|array $condition
     * @param string $fieldname
     */
    public function addQueryCondition(string|array $condition, string $fieldname = ''): void;

    /**
     * Reset filter condition for fieldname
     *
     * @param string $fieldname
     *
     * @return void
     */
    public function resetCondition(string $fieldname): void;

    /**
     * Reset query condition for fieldname
     *
     * @param string $fieldname
     */
    public function resetQueryCondition(string $fieldname): void;

    /**
     * Adds relation condition to product list
     *
     * @param string $fieldname
     * @param string|array $condition
     */
    public function addRelationCondition(string $fieldname, string|array $condition): void;

    /**
     * Resets all conditions of product list
     */
    public function resetConditions(): void;

    /**
     * Adds price condition to product list
     *
     * @param float|null $from
     * @param float|null $to
     */
    public function addPriceCondition(float $from = null, float $to = null): void;

    public function setInProductList(bool $inProductList): void;

    public function getInProductList(): bool;

    /**
     * sets order direction
     *
     * @param string $order
     */
    public function setOrder(string $order): void;

    /**
     * gets order direction
     */
    public function getOrder(): ?string;

    /**
     * sets order key
     *
     * @param array|string $orderKey either single field name, or array of field names or array of arrays (field name, direction)
     */
    public function setOrderKey(array|string $orderKey): void;

    public function getOrderKey(): array|string;

    public function setLimit(int $limit): void;

    public function getLimit(): ?int;

    public function setOffset(int $offset): void;

    public function getOffset(): int;

    public function setCategory(AbstractCategory $category): void;

    public function getCategory(): ?AbstractCategory;

    public function setVariantMode(string $variantMode): void;

    public function getVariantMode(): string;

    /**
     * loads search results from index and returns them
     *
     * @return IndexableInterface[]
     */
    public function load(): array;

    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded
     *
     * @return void
     */
    public function prepareGroupByValues(string $fieldname, bool $countValues = false, bool $fieldnameShouldBeExcluded = true): void;

    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded
     *
     * @return void
     */
    public function prepareGroupByRelationValues(string $fieldname, bool $countValues = false, bool $fieldnameShouldBeExcluded = true): void;

    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded
     *
     * @return void
     */
    public function prepareGroupBySystemValues(string $fieldname, bool $countValues = false, bool $fieldnameShouldBeExcluded = true): void;

    /**
     * resets all set prepared group by values
     *
     * @return void
     */
    public function resetPreparedGroupByValues(): void;

    /**
     * loads group by values based on fieldname either from local variable if prepared or directly from product index
     *
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getGroupByValues(string $fieldname, bool $countValues = false, bool $fieldnameShouldBeExcluded = true): array;

    /**
     * loads group by values based on relation fieldname either from local variable if prepared or directly from product index
     *
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getGroupByRelationValues(string $fieldname, bool $countValues = false, bool $fieldnameShouldBeExcluded = true): array;

    /**
     * loads group by values based on relation fieldname either from local variable if prepared or directly from product index
     *
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getGroupBySystemValues(string $fieldname, bool $countValues = false, bool $fieldnameShouldBeExcluded = true): array;
}
