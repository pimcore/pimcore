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

class DefaultCustomReportAdapterFactory implements CustomReportAdapterFactoryInterface
{
    private string $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function create(stdClass $config, Config $fullConfig = null): CustomReportAdapterInterface
    {
        return new $this->className($config, $fullConfig);
    }
}
