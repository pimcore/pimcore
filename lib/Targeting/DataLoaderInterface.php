<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Targeting;

use Pimcore\Targeting\DataProvider\DataProviderInterface;
use Pimcore\Targeting\Model\VisitorInfo;

interface DataLoaderInterface
{
    /**
     * Loads data from given data providers while taking
     * data provider dependencies into account
     *
     * @param VisitorInfo $visitorInfo
     * @param array|string $providerKeys
     */
    public function loadDataFromProviders(VisitorInfo $visitorInfo, $providerKeys);

    /**
     * Checks if a data provider is registered
     *
     * @param string $type
     *
     * @return bool
     */
    public function hasDataProvider(string $type): bool;

    /**
     * Returns the data provider instance identified by name
     *
     * @param string $type
     *
     * @return DataProviderInterface
     */
    public function getDataProvider(string $type): DataProviderInterface;
}
