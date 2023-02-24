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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting;

use Pimcore\Bundle\PersonalizationBundle\Targeting\DataProvider\DataProviderInterface;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;

interface DataLoaderInterface
{
    /**
     * Loads data from given data providers while taking
     * data provider dependencies into account
     */
    public function loadDataFromProviders(VisitorInfo $visitorInfo, array|string $providerKeys): void;

    /**
     * Checks if a data provider is registered
     */
    public function hasDataProvider(string $type): bool;

    /**
     * Returns the data provider instance identified by name
     */
    public function getDataProvider(string $type): DataProviderInterface;
}
