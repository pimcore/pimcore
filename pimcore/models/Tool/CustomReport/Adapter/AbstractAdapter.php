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
 * @category   Pimcore
 * @package    Pimcore
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\CustomReport\Adapter;

abstract class AbstractAdapter
{

    /**
     * @param $config
     * @param null $fullConfig
     */
    public function __construct($config, $fullConfig = null)
    {
        $this->config = $config;
        $this->fullConfig = $fullConfig;
    }

    /**
     * returns data for given parameters
     *
     * @param $filters
     * @param $sort
     * @param $dir
     * @param $offset
     * @param $limit
     * @param null $fields - if set, only in fields specified columns are returned
     * @param null $drillDownFilters - if set, additional filters are set
     * @return array
     */
    abstract public function getData($filters, $sort, $dir, $offset, $limit, $fields = null, $drillDownFilters = null);

    /**
     * returns available columns for given configuration
     *
     * @param $configuration
     * @return mixed
     */
    abstract public function getColumns($configuration);

    /**
     * returns all available values for given field with given filters and drillDownFilters
     *
     * @param $filters
     * @param $field
     * @param $drillDownFilters
     * @return mixed
     */
    abstract public function getAvailableOptions($filters, $field, $drillDownFilters);
}
