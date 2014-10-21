<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Pimcore
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\CustomReport\Adapter;

use Pimcore\Model;

abstract class AbstractAdapter {

    /**
     * @param $config
     * @param null $fullConfig
     */
    public function __construct($config, $fullConfig = null) {
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
    public abstract function getData($filters, $sort, $dir, $offset, $limit, $fields = null, $drillDownFilters = null);

    /**
     * returns available columns for given configuration
     *
     * @param $configuration
     * @return mixed
     */
    public abstract function getColumns($configuration);

    /**
     * returns all available values for given field with given filters and drillDownFilters
     *
     * @param $filters
     * @param $field
     * @param $drillDownFilters
     * @return mixed
     */
    public abstract function getAvailableOptions($filters, $field, $drillDownFilters);

}