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

use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use stdClass;

abstract class AbstractAdapter implements CustomReportAdapterInterface
{
    protected stdClass $config;

    protected ?Config $fullConfig = null;

    public function __construct(stdClass $config, Config $fullConfig = null)
    {
        $this->config = $config;
        $this->fullConfig = $fullConfig;
    }

    abstract public function getData(?array $filters, ?string $sort, ?string $dir, ?int $offset, ?int $limit, array $fields = null, array $drillDownFilters = null): array;

    abstract public function getColumns(?stdClass $configuration): array;

    abstract public function getAvailableOptions(array $filters, string $field, array $drillDownFilters): array;
}
