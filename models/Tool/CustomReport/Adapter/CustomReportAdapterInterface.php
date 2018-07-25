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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\CustomReport\Adapter;

interface CustomReportAdapterInterface
{
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
     *
     * @return array
     */
    public function getData($filters, $sort, $dir, $offset, $limit, $fields = null, $drillDownFilters = null);

    /**
     * returns available columns for given configuration
     *
     * @param $configuration
     *
     * @return mixed
     */
    public function getColumns($configuration);

    /**
     * returns all available values for given field with given filters and drillDownFilters
     *
     * @param $filters
     * @param $field
     * @param $drillDownFilters
     *
     * @return mixed
     */
    public function getAvailableOptions($filters, $field, $drillDownFilters);
}
