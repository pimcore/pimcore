<?php

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

namespace Pimcore\Model\Tool\CustomReport\Adapter;

use Pimcore\Model\Tool\CustomReport\Config;

abstract class AbstractAdapter implements CustomReportAdapterInterface
{
    /**
     * @var \stdClass
     */
    protected $config;

    /**
     * @var Config|null
     */
    protected $fullConfig;

    /**
     * @param \stdClass $config
     * @param Config|null $fullConfig
     */
    public function __construct(\stdClass $config, ?Config $fullConfig = null)
    {
        $this->config = $config;
        $this->fullConfig = $fullConfig;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getData(?array $filters, ?string $sort, ?string $dir, ?int $offset, ?int $limit, ?array $fields = null, ?array $drillDownFilters = null);

    /**
     * {@inheritdoc}
     */
    abstract public function getColumns(?\stdClass $configuration);

    /**
     * {@inheritdoc}
     */
    abstract public function getAvailableOptions(array $filters, string $field, array $drillDownFilters);
}
