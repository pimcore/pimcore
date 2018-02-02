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

namespace Pimcore\Model\Tool\CustomReport\Adapter;

class Analytics extends AbstractAdapter
{
    /**
     * @param $filters
     * @param $sort
     * @param $dir
     * @param $offset
     * @param $limit
     * @param null $fields
     * @param null $drillDownFilters
     * @param null $fullConfig
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getData($filters, $sort, $dir, $offset, $limit, $fields = null, $drillDownFilters = null, $fullConfig = null)
    {
        $this->setFilters($filters, $drillDownFilters);

        if ($sort) {
            $dir = $dir == 'DESC' ? '-' : '';
            $this->config->sort = $dir.$sort;
        }

        if ($offset) {
            $this->config->startIndex = $offset;
        }

        if ($limit) {
            $this->config->maxResults = $limit;
        }

        $results = $this->getDataHelper($fields, $drillDownFilters);
        $data = $this->extractData($results);

        return [ 'data' => $data, 'total' => $results['totalResults'] ];
    }

    /**
     * @param $configuration
     *
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function getColumns($configuration)
    {
        $result = $this->getDataHelper();
        $columns = [];

        foreach ($result['columnHeaders'] as $col) {
            $columns[] = $col['name'];
        }

        return $columns;
    }

    /**
     * @param $filters
     * @param array $drillDownFilters
     */
    protected function setFilters($filters, $drillDownFilters = [])
    {
        $gaFilters = [ $this->config->filters ];
        if (sizeof($filters)) {
            foreach ($filters as $filter) {
                if ($filter['type'] == 'string') {
                    $value = str_replace(';', '', addslashes($filter['value']));
                    $gaFilters[] = "{$filter['field']}=~{$value}";
                } elseif ($filter['type'] == 'numeric') {
                    $value = floatval($filter['value']);
                    $compMapping = [
                        'lt' => '<',
                        'gt' => '>',
                        'eq' => '=='
                    ];
                    if ($compMapping[$filter['comparison']]) {
                        $gaFilters[] = "{$filter['field']}{$compMapping[$filter['comparison']]}{$value}";
                    }
                } elseif ($filter['type'] == 'boolean') {
                    $value = $filter['value'] ? 'Yes' : 'No';
                    $gaFilters[] = "{$filter['field']}=={$value}";
                }
            }
        }

        if (sizeof($drillDownFilters)) {
            foreach ($drillDownFilters as $key => $value) {
                $gaFilters[] = "{$key}=={$value}";
            }
        }

        foreach ($gaFilters as $key => $filter) {
            if (!$filter) {
                unset($gaFilters[$key]);
            }
        }

        $this->config->filters = implode(';', $gaFilters);
    }

    /**
     * @param null $fields
     * @param null $drillDownFilters
     * @param bool $useDimensionHandling
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function getDataHelper($fields = null, $drillDownFilters = null, $useDimensionHandling = true)
    {
        $configuration = clone $this->config;

        if (is_array($fields) && sizeof($fields)) {
            $configuration = $this->handleFields($configuration, $fields);
        }

        if ($this->fullConfig && $useDimensionHandling) {
            $configuration = $this->handleDimensions($configuration);
        }

        $client = \Pimcore\Google\Api::getServiceClient();
        if (!$client) {
            throw new \Exception('Google Analytics is not configured');
        }

        $service = new \Google_Service_Analytics($client);

        if (!$configuration->profileId) {
            throw new \Exception('no profileId given');
        }

        if (!$configuration->metric) {
            throw new \Exception('no metric given');
        }

        $options = [];

        if ($configuration->filters) {
            $options['filters'] = is_array($configuration->filters) ? implode(',', $configuration->filters) : $configuration->filters;
        }
        if ($configuration->dimension) {
            $options['dimensions'] = is_array($configuration->dimension) ? implode(',', $configuration->dimension) : $configuration->dimension;
        }
        if ($configuration->sort) {
            $options['sort'] = $configuration->sort;
        }
        if ($configuration->startIndex) {
            $options['start-index'] = $configuration->startIndex;
        }
        if ($configuration->maxResults) {
            $options['max-results'] = $configuration->maxResults;
        }
        if ($configuration->segment) {
            $options['segment'] = $configuration->segment;
        }

        $configuration->startDate = $this->calcDate($configuration->startDate, $configuration->relativeStartDate);
        $configuration->endDate = $this->calcDate($configuration->endDate, $configuration->relativeEndDate);

        if (!$configuration->startDate) {
            throw new \Exception('no start date given');
        }

        if (!$configuration->endDate) {
            throw new \Exception('no end date given');
        }

        return $service->data_ga->get('ga:'.$configuration->profileId, date('Y-m-d', $configuration->startDate), date('Y-m-d', $configuration->endDate), (is_array($configuration->metric) ? implode(',', $configuration->metric) : $configuration->metric), $options);
    }

    /**
     * @param $results
     *
     * @return array
     */
    protected function extractData($results)
    {
        $data = [];

        if ($results['rows']) {
            foreach ($results['rows'] as $row) {
                $entry = [];
                foreach ($results['columnHeaders'] as $key => $header) {
                    $entry[$header['name']] = $row[$key];
                }
                $data[] = $entry;
            }
        }

        return $data;
    }

    /**
     * @param $configuration
     * @param $fields
     *
     * @return mixed
     */
    protected function handleFields($configuration, $fields)
    {
        $metrics = $configuration->metric;
        foreach ($metrics as $key => $metric) {
            if (!in_array($metric, $fields)) {
                unset($metrics[$key]);
            }
        }
        $configuration->metric = implode(',', $metrics);

        $dimensions = $configuration->dimension;
        foreach ($dimensions as $key => $dimension) {
            if (!in_array($dimension, $fields)) {
                unset($dimensions[$key]);
            }
        }
        $configuration->dimension = implode(',', $dimensions);

        return $configuration;
    }

    /**
     * @param $configuration
     *
     * @return mixed
     */
    protected function handleDimensions($configuration)
    {
        $dimension = $configuration->dimension;
        if (sizeof($dimension)) {
            foreach ($this->fullConfig->columnConfiguration as $column) {
                if ($column['filter_drilldown'] == 'only_filter') {
                    foreach ($dimension as $key => $dim) {
                        if ($dim == $column['name']) {
                            unset($dimension[$key]);
                        }
                    }
                }
            }
        }
        $configuration->dimension = implode(',', $dimension);

        return $configuration;
    }

    /**
     * @param $date
     * @param $relativeDate
     *
     * @return float|int|string
     */
    protected function calcDate($date, $relativeDate)
    {
        if (strpos($relativeDate, '-') !== false || strpos($relativeDate, '+') !== false) {
            $modifiers = explode(' ', str_replace('  ', ' ', $relativeDate));

            $applyModifiers = [];
            foreach ($modifiers as $modifier) {
                $modifier = trim($modifier);
                if (preg_match('/^([+-])(\d+)([dmy])$/', $modifier, $matches)) {
                    if (in_array($matches[1], ['+', '-']) && is_numeric($matches[2])
                        && in_array($matches[3], ['d', 'm', 'y'])
                    ) {
                        $applyModifiers[] = ['sign' => $matches[1], 'number' => $matches[2],
                            'type' => $matches[3]];
                    }
                }
            }

            if (sizeof($applyModifiers)) {
                $date = new \DateTime();

                foreach ($applyModifiers as $modifier) {
                    if ($modifier['sign'] == '-') {
                        $date->sub(new \DateInterval('P' . $modifier['number'] . strtoupper($modifier['type'])));
                    } else {
                        $date->add(new \DateInterval('P' . $modifier['number'] . strtoupper($modifier['type'])));
                    }
                }

                return $date->getTimestamp();
            }
        }

        return $date / 1000;
    }

    /**
     * @param $filters
     * @param $field
     * @param $drillDownFilters
     *
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function getAvailableOptions($filters, $field, $drillDownFilters)
    {
        $this->setFilters($filters, $drillDownFilters);
        $results = $this->getDataHelper([], $drillDownFilters, false);

        $data = $this->extractData($results);

        $return = [];
        foreach ($data as $row) {
            $return[] = ['value'=>$row[$field]];
        }

        return ['data'=> $return];
    }
}
