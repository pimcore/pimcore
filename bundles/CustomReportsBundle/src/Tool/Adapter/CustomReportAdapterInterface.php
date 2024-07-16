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

namespace Pimcore\Bundle\CustomReportsBundle\Tool\Adapter;

use stdClass;

interface CustomReportAdapterInterface
{
    /**
     * returns data for given parameters
     *
     * @param array|null $fields - if set, only in fields specified columns are returned
     * @param array|null $drillDownFilters - if set, additional filters are set
     *
     */
    public function getData(?array $filters, ?string $sort, ?string $dir, ?int $offset, ?int $limit, array $fields = null, array $drillDownFilters = null): array;

    /**
     * returns available columns for given configuration
     *
     *
     */
    public function getColumns(?stdClass $configuration): array;

    /**
     * returns all available values for given field with given filters and drillDownFilters
     *
     *
     */
    public function getAvailableOptions(array $filters, string $field, array $drillDownFilters): array;
}
