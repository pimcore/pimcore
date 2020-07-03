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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ElasticSearch;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;

/**
 * Implementation of product list which works based on the product index of the online shop framework
 */
class DefaultElasticSearch5 extends AbstractElasticSearch
{
    protected function getQueryType()
    {
        switch ($this->getVariantMode()) {
            case ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT:
                return self::PRODUCT_TYPE_OBJECT;
            case ProductListInterface::VARIANT_MODE_HIDE:
                return self::PRODUCT_TYPE_OBJECT;
            case ProductListInterface::VARIANT_MODE_VARIANTS_ONLY:
                return self::PRODUCT_TYPE_VARIANT;
            case ProductListInterface::VARIANT_MODE_INCLUDE:
                return self::PRODUCT_TYPE_OBJECT . ','. self::PRODUCT_TYPE_VARIANT;
        }
    }

    /**
     * loads all prepared group by values
     *   1 - get general filter (= filter of fields don't need to be considered in group by values or where fieldnameShouldBeExcluded set to false)
     *   2 - for each group by value create a own aggregation section with all other group by filters added
     *
     * @throws \Exception
     */
    protected function doLoadGroupByValues()
    {
        // create general filters and queries
        $toExcludeFieldnames = [];
        foreach ($this->preparedGroupByValues as $fieldname => $config) {
            if ($config['fieldnameShouldBeExcluded']) {
                $toExcludeFieldnames[$this->getTenantConfig()->getReverseMappedFieldName($fieldname)] = $fieldname;
            }
        }

        $boolFilters = [];
        $queryFilters = [];

        //pre conditions
        $boolFilters = $this->buildSystemConditions($boolFilters);

        //user specific filters
        $boolFilters = $this->buildFilterConditions($boolFilters, $toExcludeFieldnames);

        //relation conditions
        $boolFilters = $this->buildRelationConditions($boolFilters, $toExcludeFieldnames);

        //query conditions
        $queryFilters = $this->buildQueryConditions($queryFilters, []);

        $aggregations = [];

        //calculate already filtered attributes
        $filteredFieldnames = [];
        foreach ($this->filterConditions as $fieldname => $condition) {
            if (!array_key_exists($fieldname, $toExcludeFieldnames)) {
                $filteredFieldnames[$fieldname] = $fieldname;
            }
        }
        foreach ($this->relationConditions as $fieldname => $condition) {
            if (!array_key_exists($fieldname, $toExcludeFieldnames)) {
                $filteredFieldnames[$fieldname] = $fieldname;
            }
        }

        foreach ($this->preparedGroupByValues as $fieldname => $config) {
            $shortFieldname = $this->getTenantConfig()->getReverseMappedFieldName($fieldname);

            $specificFilters = [];
            //user specific filters
            $specificFilters = $this->buildFilterConditions($specificFilters, array_merge($filteredFieldnames, [$shortFieldname => $shortFieldname]));
            //relation conditions
            $specificFilters = $this->buildRelationConditions($specificFilters, array_merge($filteredFieldnames, [$shortFieldname => $shortFieldname]));

            if ($specificFilters) {
                $aggregations[$fieldname] = [
                    'filter' => [
                        'bool' => [
                            'must' => $specificFilters,
                        ],
                    ],
                    'aggs' => [
                        $fieldname => [
                            'terms' => ['field' => $fieldname, 'size' => self::INTEGER_MAX_VALUE, 'order' => ['_term' => 'asc' ]],
                        ],
                    ],
                ];

                //necessary to calculate correct counts of search results for filter values
                if ($this->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                    $aggregations[$fieldname]['aggs'][$fieldname]['aggs'] = [
                        'objectCount' => ['cardinality' => ['field' => 'system.o_virtualProductId']],
                    ];
                }
            } else {
                $aggregations[$fieldname] = [
                    'terms' => ['field' => $fieldname, 'size' => self::INTEGER_MAX_VALUE, 'order' => ['_term' => 'asc' ]],
                ];

                //necessary to calculate correct counts of search results for filter values
                if ($this->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                    $aggregations[$fieldname]['aggs'] = [
                        'objectCount' => ['cardinality' => ['field' => 'system.o_virtualProductId']],
                    ];
                }
            }
        }

        if ($aggregations) {
            $params = [];
            $params['index'] = $this->getIndexName();
            $params['body']['_source'] = false;
            $params['body']['size'] = 0;
            $params['body']['from'] = $this->getOffset();
            $params['body']['aggs'] = $aggregations;

            $variantModeForAggregations = $this->getVariantMode();
            if ($this->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                $params['type'] = ProductListInterface::PRODUCT_TYPE_VARIANT;
                $variantModeForAggregations = ProductListInterface::VARIANT_MODE_VARIANTS_ONLY;
            } else {
                $params['type'] = $this->getQueryType();
            }

            // build query for request
            $params = $this->buildQuery($params, $boolFilters, $queryFilters, $variantModeForAggregations);

            // send request
            $result = $this->sendRequest($params);

            if ($result['aggregations']) {
                foreach ($result['aggregations'] as $fieldname => $aggregation) {
                    if ($aggregation['buckets']) {
                        $buckets = $aggregation['buckets'];
                    } else {
                        $buckets = $aggregation[$fieldname]['buckets'];
                    }

                    $groupByValueResult = [];
                    if ($buckets) {
                        foreach ($buckets as $bucket) {
                            if ($this->getVariantMode() == self::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                                $groupByValueResult[] = ['value' => $bucket['key'], 'count' => $bucket['objectCount']['value']];
                            } else {
                                $groupByValueResult[] = ['value' => $bucket['key'], 'count' => $bucket['doc_count']];
                            }
                        }
                    }

                    $this->preparedGroupByValuesResults[$fieldname] = $groupByValueResult;
                }
            }
        } else {
            $this->preparedGroupByValuesResults = [];
        }

        $this->preparedGroupByValuesLoaded = true;
    }

    /**
     * build the complete query
     *
     * @param array $params
     * @param array $boolFilters
     * @param array $queryFilters
     * @param string|null $variantMode
     *
     * @return array
     */
    protected function buildQuery(array $params, array $boolFilters, array $queryFilters, string $variantMode = null)
    {
        if (!$variantMode) {
            $variantMode = $this->getVariantMode();
        }

        if ($variantMode == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
            $params['body']['query']['bool']['must']['has_child']['type'] = self::PRODUCT_TYPE_VARIANT;
            $params['body']['query']['bool']['must']['has_child']['score_mode'] = 'avg';
            $params['body']['query']['bool']['must']['has_child']['query']['bool']['must'] = $queryFilters;

            $params['body']['query']['bool']['filter']['has_child']['type'] = self::PRODUCT_TYPE_VARIANT;
            $params['body']['query']['bool']['filter']['has_child']['filter']['bool']['must'] = $boolFilters;
        } else {
            $params['body']['query']['bool']['must']['bool']['must'] = $queryFilters;
            $params['body']['query']['bool']['filter']['bool']['must'] = $boolFilters;
        }

        return $params;
    }

    /**
     * Returns the Elasticsearch query parameters
     *
     * @return array
     */
    public function getQuery()
    {
        $boolFilters = [];
        $queryFilters = [];

        //pre conditions
        $boolFilters = $this->buildSystemConditions($boolFilters);

        //user specific filters
        $boolFilters = $this->buildFilterConditions($boolFilters, []);

        //relation conditions
        $boolFilters = $this->buildRelationConditions($boolFilters, []);

        //query conditions
        $queryFilters = $this->buildQueryConditions($queryFilters, []);

        $params = [];
        $params['index'] = $this->getIndexName();
        $params['type'] = $this->getQueryType();
        $params['body']['_source'] = true;
        $params['body']['size'] = $this->getLimit();
        $params['body']['from'] = $this->getOffset();

        if ($this->orderKey) {
            if (is_array($this->orderKey)) {
                foreach ($this->orderKey as $orderKey) {
                    $params['body']['sort'][] = [$this->tenantConfig->getFieldNameMapped($orderKey[0]) => (strtolower($orderKey[1]) ?: 'asc')];
                }
            } else {
                $params['body']['sort'][] = [$this->tenantConfig->getFieldNameMapped($this->orderKey) => ($this->order ?: 'asc')];
            }
        }

        if ($aggs = $this->getSearchAggregation()) {
            foreach ($aggs as $name => $type) {
                $params['body']['aggs'][$name] = $type;
            }
        }

        // build query for request
        $params = $this->buildQuery($params, $boolFilters, $queryFilters);

        return $params;
    }
}
