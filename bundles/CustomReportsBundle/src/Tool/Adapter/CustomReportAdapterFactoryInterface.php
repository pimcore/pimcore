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
use stdClass;

interface CustomReportAdapterFactoryInterface
{
    /**
     * Create a CustomReport Adapter
     *
     *
     */
    public function create(stdClass $config, ?Config $fullConfig = null): CustomReportAdapterInterface;
}
