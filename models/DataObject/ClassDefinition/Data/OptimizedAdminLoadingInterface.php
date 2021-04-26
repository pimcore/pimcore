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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

interface OptimizedAdminLoadingInterface
{
    /**
     * e.g. load relations directly from relations table and if necessary additional data
     * (like object attributes or meta data) asynchronously when the UI is ready
     *
     * @return bool
     */
    public function isOptimizedAdminLoading(): bool;
}
