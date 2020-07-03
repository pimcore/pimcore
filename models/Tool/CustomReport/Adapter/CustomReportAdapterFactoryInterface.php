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

use Pimcore\Model\Tool\CustomReport\Config;

interface CustomReportAdapterFactoryInterface
{
    /**
     * Create a CustomReport Adapter
     *
     * @param \stdClass $config
     * @param Config|null $fullConfig
     *
     * @return CustomReportAdapterInterface
     */
    public function create($config, $fullConfig = null);
}
