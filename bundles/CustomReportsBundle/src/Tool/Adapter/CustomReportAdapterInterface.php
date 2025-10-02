<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CustomReportsBundle\Tool\Adapter;

use Pimcore\Bundle\CustomReportsBundle\Tool\Config\ColumnInformation;
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
    public function getData(
        ?array $filters,
        ?string $sort,
        ?string $dir,
        ?int $offset,
        ?int $limit,
        ?array $fields = null,
        ?array $drillDownFilters = null
    ): array;

    /**
     * returns available columns for given configuration
     *
     *
     */
    public function getColumns(?stdClass $configuration): array;

    /**
     * returns available columns for given configuration
     *
     * @return ColumnInformation[]
     */
    public function getColumnsWithMetadata(?stdClass $configuration): array;

    /**
     * returns all available values for given field with given filters and drillDownFilters
     *
     *
     */
    public function getAvailableOptions(array $filters, string $field, array $drillDownFilters): array;

    /**
     * returns if pagination is activated or deactivated
     */
    public function getPagination(): bool;
}
