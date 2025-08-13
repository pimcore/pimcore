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

use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config\ColumnInformation;
use stdClass;

abstract class AbstractAdapter implements CustomReportAdapterInterface
{
    protected stdClass $config;

    protected ?Config $fullConfig = null;

    public function __construct(stdClass $config, ?Config $fullConfig = null)
    {
        $this->config = $config;
        $this->fullConfig = $fullConfig;
    }

    abstract public function getData(
        ?array $filters,
        ?string $sort,
        ?string $dir,
        ?int $offset,
        ?int $limit,
        ?array $fields = null,
        ?array $drillDownFilters = null
    ): array;

    abstract public function getColumns(?stdClass $configuration): array;

    public function getColumnsWithMetadata(?stdClass $configuration): array
    {
        $columnsWithMetadata = [];
        $columns = $this->getColumns($configuration);

        foreach ($columns as $column) {
            $columnsWithMetadata[] = new ColumnInformation(
                $column,
                false,
                false,
                false,
                false
            );
        }

        return $columnsWithMetadata;
    }

    abstract public function getAvailableOptions(array $filters, string $field, array $drillDownFilters): array;

    public function getPagination(): bool
    {
        return true;
    }
}
