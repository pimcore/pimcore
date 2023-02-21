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

use Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\MysqlConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Psr\Log\LoggerInterface;

/**
 * Implementation of product list which works based on the product index of the online shop framework
 */
class DefaultMysql implements ProductListInterface
{
    /**
     * @var null|IndexableInterface[]
     */
    protected ?array $products = null;

    protected string $tenantName;

    protected MysqlConfigInterface $tenantConfig;

    protected ?int $totalCount = null;

    protected string $variantMode = ProductListInterface::VARIANT_MODE_INCLUDE;

    protected ?int $limit = null;

    protected int $offset = 0;

    protected ?AbstractCategory $category = null;

    protected ?DefaultMysql\Dao $resource = null;

    protected bool $inProductList = true;

    protected LoggerInterface $logger;

    public function __construct(MysqlConfigInterface $tenantConfig, LoggerInterface $pimcoreEcommerceSqlLogger)
    {
        $this->tenantName = $tenantConfig->getTenantName();
        $this->tenantConfig = $tenantConfig;

        $this->logger = $pimcoreEcommerceSqlLogger;
        $this->resource = new DefaultMysql\Dao($this, $this->logger);
    }

    /** @inheritDoc */
    public function getProducts(): array
    {
        if ($this->products === null) {
            $this->load();
        }

        return $this->products;
    }

    protected array $conditions = [];

    /**
     * @var array<string[]>
     */
    protected array $relationConditions = [];

    /**
     * @var string[][]
     */
    protected array $queryConditions = [];

    protected ?float $conditionPriceFrom = null;

    protected ?float $conditionPriceTo = null;

    public function addCondition(array|string $condition, string $fieldname = ''): void
    {
        $this->products = null;
        $this->conditions[$fieldname][] = $condition;
    }

    public function resetCondition(string $fieldname): void
    {
        $this->products = null;
        unset($this->conditions[$fieldname]);
    }

    public function addRelationCondition(string $fieldname, string|array $condition): void
    {
        $this->products = null;
        $this->relationConditions[$fieldname][] = '`fieldname` = ' . $this->quote($fieldname) . ' AND '  . $condition;
    }

    /**
     * resets all conditions of product list
     */
    public function resetConditions(): void
    {
        $this->conditions = [];
        $this->relationConditions = [];
        $this->queryConditions = [];
        $this->conditionPriceFrom = null;
        $this->conditionPriceTo = null;
        $this->products = null;
    }

    /**
     * Adds query condition to product list for fulltext search
     * Fieldname is optional but highly recommended - needed for resetting condition based on fieldname
     * and exclude functionality in group by results
     *
     * @param string|array $condition
     * @param string $fieldname
     */
    public function addQueryCondition(string|array $condition, string $fieldname = ''): void
    {
        $this->products = null;
        $this->queryConditions[$fieldname][] = $condition;
    }

    /**
     * Reset query condition for fieldname
     *
     * @param string $fieldname
     */
    public function resetQueryCondition(string $fieldname): void
    {
        $this->products = null;
        unset($this->queryConditions[$fieldname]);
    }

    /**
     * @param float|null $from
     * @param float|null $to
     */
    public function addPriceCondition(?float $from = null, ?float $to = null): void
    {
        $this->products = null;
        $this->conditionPriceFrom = $from;
        $this->conditionPriceTo = $to;
    }

    public function setInProductList(bool $inProductList): void
    {
        $this->products = null;
        $this->inProductList = $inProductList;
    }

    public function getInProductList(): bool
    {
        return $this->inProductList;
    }

    protected ?string $order = null;

    protected null|string|array $orderKey = null;

    protected bool $orderByPrice = false;

    public function setOrder(string $order): void
    {
        $this->products = null;
        $this->order = $order;
    }

    public function getOrder(): ?string
    {
        return $this->order;
    }

    /**
     * @param array|string $orderKey either single field name, or array of field names or array of arrays (field name, direction)
     */
    public function setOrderKey(array|string $orderKey): void
    {
        $this->products = null;
        if ($orderKey == ProductListInterface::ORDERKEY_PRICE) {
            $this->orderByPrice = true;
        } else {
            $this->orderByPrice = false;
        }

        $this->orderKey = $orderKey;
    }

    public function getOrderKey(): array|string
    {
        return $this->orderKey;
    }

    public function setLimit(int $limit): void
    {
        if ($this->limit != $limit) {
            $this->products = null;
        }
        $this->limit = $limit;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setOffset(int $offset): void
    {
        if ($this->offset != $offset) {
            $this->products = null;
        }
        $this->offset = $offset;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setCategory(AbstractCategory $category): void
    {
        $this->products = null;
        $this->category = $category;
    }

    public function getCategory(): ?AbstractCategory
    {
        return $this->category;
    }

    public function setVariantMode(string $variantMode): void
    {
        $this->products = null;
        $this->variantMode = $variantMode;
    }

    public function getVariantMode(): string
    {
        return $this->variantMode;
    }

    public function load(): array
    {
        $objectRaws = [];

        //First case: no price filtering and no price sorting
        if (!$this->orderByPrice && $this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            $objectRaws = $this->loadWithoutPriceFilterWithoutPriceSorting();
        }

        //Second case: no price filtering but price sorting
        elseif ($this->orderByPrice && $this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            $objectRaws = $this->loadWithoutPriceFilterWithPriceSorting();
        }

        //Third case: price filtering but no price sorting
        elseif (!$this->orderByPrice && ($this->conditionPriceFrom !== null || $this->conditionPriceTo !== null)) {
            $objectRaws = $this->loadWithPriceFilterWithoutPriceSorting();
        }

        //Forth case: price filtering and price sorting
        elseif ($this->orderByPrice && ($this->conditionPriceFrom !== null || $this->conditionPriceTo !== null)) {
            $objectRaws = $this->loadWithPriceFilterWithPriceSorting();
        }

        $this->products = [];
        foreach ($objectRaws as $raw) {
            $product = $this->loadElementById($raw['id']);
            if ($product) {
                $this->products[] = $product;
            }
        }

        return $this->products;
    }

    /**
     * First case: no price filtering and no price sorting
     *
     * @return array
     */
    protected function loadWithoutPriceFilterWithoutPriceSorting(): array
    {
        $objectRaws = $this->resource->load($this->buildQueryFromConditions(), $this->buildOrderBy(), $this->getLimit(), $this->getOffset());
        $this->totalCount = $this->resource->getLastRecordCount();

        return $objectRaws;
    }

    /**
     * Second case: no price filtering but price sorting
     *
     * @return array
     *
     * @throws \Exception
     *
     * @todo Not implemented yet
     */
    protected function loadWithoutPriceFilterWithPriceSorting(): array
    {
        $objectRaws = $this->resource->load($this->buildQueryFromConditions());
        $this->totalCount = $this->resource->getLastRecordCount();

        $priceSystemArrays = [];
        foreach ($objectRaws as $raw) {
            $priceSystemArrays[$raw['priceSystemName']][] = $raw['id'];
        }
        if (count($priceSystemArrays) == 1) {
            $priceSystemName = key($priceSystemArrays);
            $priceSystem = Factory::getInstance()->getPriceSystem($priceSystemName);
            $objectRaws = $priceSystem->filterProductIds($priceSystemArrays[$priceSystemName], null, null, $this->order, $this->getOffset(), $this->getLimit());
        } elseif (count($priceSystemArrays) == 0) {
            //nothing to do
        } else {
            throw new \Exception('Not implemented yet - multiple pricing systems are not supported yet');
        }

        return $objectRaws;
    }

    /**
     * Third case: price filtering but no price sorting
     *
     * @return array
     *
     * @throws \Exception
     *
     * @todo Not implemented yet
     */
    protected function loadWithPriceFilterWithoutPriceSorting(): array
    {
        //check number of price systems
        //set $this->totalCount
        throw new \Exception('Not implemented yet');
    }

    /**
     * Forth case: price filtering and price sorting
     *
     * @return array
     *
     * @throws \Exception
     *
     * @todo Not implemented yet
     */
    protected function loadWithPriceFilterWithPriceSorting(): array
    {
        //check number of price systems
        //set $this->totalCount
        throw new \Exception('Not implemented yet');
    }

    /**
     * loads element by id
     *
     * @param int $elementId
     *
     * @return IndexableInterface|null
     */
    protected function loadElementById(int $elementId): ?IndexableInterface
    {
        return $this->getCurrentTenantConfig()->getObjectMockupById($elementId);
    }

    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     *
     */
    public function prepareGroupByValues(string $fieldname, bool $countValues = false, bool $fieldnameShouldBeExcluded = true): void
    {
        // not supported with mysql tables
    }

    /**
     * resets all set prepared group by values
     *
     * @return void
     */
    public function resetPreparedGroupByValues(): void
    {
        // not supported with mysql tables
    }

    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     *
     */
    public function prepareGroupByRelationValues(string $fieldname, bool $countValues = false, bool $fieldnameShouldBeExcluded = true): void
    {
        // not supported with mysql tables
    }

    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     *
     */
    public function prepareGroupBySystemValues(string $fieldname, bool $countValues = false, bool $fieldnameShouldBeExcluded = true): void
    {
        // not supported with mysql tables
    }

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
    public function getGroupBySystemValues(string $fieldname, bool $countValues = false, bool $fieldnameShouldBeExcluded = true): array
    {
        // not supported with mysql tables
        return [];
    }

    /**
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getGroupByValues(string $fieldname, bool $countValues = false, bool $fieldnameShouldBeExcluded = true): array
    {
        $excludedFieldName = $fieldname;
        if (!$fieldnameShouldBeExcluded) {
            $excludedFieldName = null;
        }
        if ($this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            return $this->resource->loadGroupByValues($fieldname, $this->buildQueryFromConditions(false, $excludedFieldName, $this->getVariantMode()), $countValues);
        } else {
            throw new \Exception('Not supported yet');
        }
    }

    /**
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getGroupByRelationValues(string $fieldname, bool $countValues = false, bool $fieldnameShouldBeExcluded = true): array
    {
        $excludedFieldName = $fieldname;
        if (!$fieldnameShouldBeExcluded) {
            $excludedFieldName = null;
        }
        if ($this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            return $this->resource->loadGroupByRelationValues($fieldname, $this->buildQueryFromConditions(false, $excludedFieldName), $countValues);
        } else {
            throw new \Exception('Not supported yet');
        }
    }

    protected function buildQueryFromConditions(bool $excludeConditions = false, ?string $excludedFieldname = null, ?string $variantMode = null): string
    {
        if ($variantMode == null) {
            $variantMode = $this->getVariantMode();
        }

        $preCondition = 'active = 1 AND virtualProductActive = 1';
        if ($this->inProductList) {
            $preCondition .= ' AND inProductList = 1';
        }

        $tenantCondition = $this->getCurrentTenantConfig()->getCondition();
        if ($tenantCondition) {
            $preCondition .= ' AND ' . $tenantCondition;
        }

        if ($this->getCategory()) {
            $preCondition .= " AND parentCategoryIds LIKE '%," . $this->getCategory()->getId() . ",%'";
        }

        $condition = $preCondition;

        //variant handling and userspecific conditions

        switch ($variantMode) {
            case ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT:

                //make sure, that only variant objects are considered
                $condition .= ' AND a.id != virtualProductId ';

                break;

            case ProductListInterface::VARIANT_MODE_HIDE:

                $condition .= " AND `type` != 'variant'";

                break;

            case ProductListInterface::VARIANT_MODE_VARIANTS_ONLY:

                $condition .= " AND `type` = 'variant'";

                break;
        }

        if (!$excludeConditions) {
            $userspecific = $this->buildUserspecificConditions($excludedFieldname);
            if ($userspecific) {
                $condition .= ' AND ' . $userspecific;
            }
        }

        if ($this->queryConditions) {
            $searchstring = '';
            foreach ($this->queryConditions as $queryConditionPartArray) {
                foreach ($queryConditionPartArray as $queryConditionPart) {
                    //check if there are any mysql special characters in query condition - if so, then quote condition
                    if (str_replace(['+', '-', '<', '>', '(', ')', '~', '*'], '', $queryConditionPart) != $queryConditionPart) {
                        $searchstring .= '+"' . $queryConditionPart . '" ';
                    } else {
                        $searchstring .= '+' . $queryConditionPart . '* ';
                    }
                }
            }

            $condition .= ' AND ' . $this->resource->buildFulltextSearchWhere($this->tenantConfig->getSearchAttributes(), $searchstring);
        }

        $this->logger->info('Total Condition: ' . $condition);

        return $condition;
    }

    protected function buildUserspecificConditions(?string $excludedFieldname = null): string
    {
        $condition = '';
        foreach ($this->relationConditions as $fieldname => $condArray) {
            if ($fieldname !== $excludedFieldname) {
                foreach ($condArray as $cond) {
                    if ($condition) {
                        $condition .= ' AND ';
                    }

                    $condition .= 'a.id IN (SELECT DISTINCT src FROM ' . $this->getCurrentTenantConfig()->getRelationTablename() . ' WHERE ' . $cond . ')';
                }
            }
        }

        foreach ($this->conditions as $fieldname => $condArray) {
            if ($fieldname !== $excludedFieldname) {
                foreach ($condArray as $cond) {
                    if ($condition) {
                        $condition .= ' AND ';
                    }

                    $condition .= is_array($cond)
                        ? sprintf(' ( %1$s IN (%2$s) )', $fieldname, implode(',', array_map(function ($value) {
                            return $this->quote($value);
                        }, $cond)))
                        : '(' . $cond . ')'
                    ;
                }
            }
        }

        $this->logger->info('User specific Condition Part: ' . $condition);

        return $condition;
    }

    protected function buildOrderBy(): ?string
    {
        if (!empty($this->orderKey) && $this->orderKey !== ProductListInterface::ORDERKEY_PRICE) {
            $orderKeys = $this->orderKey;
            if (!is_array($orderKeys)) {
                $orderKeys = [$orderKeys];
            }

            // add sorting for primary id to prevent mysql paging problem...
            $orderKeys[] = 'a.id';

            $directionOrderKeys = [];
            foreach ($orderKeys as $key) {
                if (is_array($key)) {
                    $directionOrderKeys[] = $key;
                } else {
                    $directionOrderKeys[] = [$key, $this->order];
                }
            }

            $orderByStringArray = [];
            foreach ($directionOrderKeys as $keyDirection) {
                $key = $keyDirection[0];
                if ($key instanceof IndexFieldSelection) {
                    $key = $key->getField();
                }
                $direction = $keyDirection[1];

                if ($this->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                    if (strtoupper($this->order) == 'DESC') {
                        $orderByStringArray[] = 'max(`' . $key . '`) ' . $direction;
                    } else {
                        $orderByStringArray[] = 'min(`' . $key . '`) ' . $direction;
                    }
                } else {
                    $orderByStringArray[] = $key . ' ' . $direction;
                }
            }

            return implode(',', $orderByStringArray);
        }

        return null;
    }

    public function quote(mixed $value): mixed
    {
        return $this->resource->quote($value);
    }

    public function getCurrentTenantConfig(): MysqlConfigInterface
    {
        return $this->tenantConfig;
    }

    /**
     * returns order by statement for simularity calculations based on given fields and object ids
     * returns cosine simularity calculation
     *
     * @param array $fields
     * @param int $objectId
     *
     * @return string
     */
    public function buildSimularityOrderBy(array $fields, int $objectId): string
    {
        return $this->resource->buildSimularityOrderBy($fields, $objectId);
    }

    /**
     * returns where statement for fulltext search index
     *
     * @param array $fields
     * @param string $searchstring
     *
     * @return string
     */
    public function buildFulltextSearchWhere(array $fields, string $searchstring): string
    {
        return $this->resource->buildFulltextSearchWhere($fields, $searchstring);
    }

    /**
     *  -----------------------------------------------------------------------------------------
     *   Methods for Iterator
     *  -----------------------------------------------------------------------------------------
     */
    public function count(): int
    {
        if ($this->totalCount === null) {
            $this->totalCount = $this->resource->getCount($this->buildQueryFromConditions());
        }

        return $this->totalCount;
    }

    /**
     * @return IndexableInterface|false
     */
    public function current(): bool|IndexableInterface
    {
        $this->getProducts();

        return current($this->products);
    }

    /**
     * Returns an collection of items for a page.
     *
     * @param int $offset Page offset
     * @param int $itemCountPerPage Number of items per page
     *
     * @return array
     */
    public function getItems(int $offset, int $itemCountPerPage): array
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);

        return $this->getProducts();
    }

    public function key(): ?int
    {
        $this->getProducts();

        return key($this->products);
    }

    public function next(): void
    {
        $this->getProducts();
        next($this->products);
    }

    public function rewind(): void
    {
        $this->getProducts();
        reset($this->products);
    }

    public function valid(): bool
    {
        return $this->current() !== false;
    }

    /**
     * @return array
     *
     * @internal
     */
    public function __sleep(): array
    {
        $vars = get_object_vars($this);

        unset($vars['resource']);
        unset($vars['products']);

        return array_keys($vars);
    }

    /**
     * @internal
     */
    public function __wakeup(): void
    {
        if (empty($this->resource)) {
            $this->resource = new DefaultMysql\Dao($this, $this->logger);
        }
    }

    /**
     * this is needed for ZF1 Paginator
     *
     * @return string
     */
    public function getCacheIdentifier(): string
    {
        return uniqid();
    }
}
