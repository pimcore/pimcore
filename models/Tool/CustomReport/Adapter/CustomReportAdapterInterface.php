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
     * @param array|null $filters
     * @param string|null $sort
     * @param string|null $dir
     * @param int|null $offset
     * @param int|null $limit
     * @param array|null $fields - if set, only in fields specified columns are returned
     * @param array|null $drillDownFilters - if set, additional filters are set
     *
     * @return array
     */
    public function getData($filters, $sort, $dir, $offset, $limit, $fields = null, $drillDownFilters = null);

    /**
     * returns available columns for given configuration
     *
     * @param \stdClass $configuration
     *
     * @return array
     */
    public function getColumns($configuration);

    /**
     * returns all available values for given field with given filters and drillDownFilters
     *
     * @param array $filters
     * @param string $field
     * @param array $drillDownFilters
     *
     * @return array
     */
    public function getAvailableOptions($filters, $field, $drillDownFilters);
}
