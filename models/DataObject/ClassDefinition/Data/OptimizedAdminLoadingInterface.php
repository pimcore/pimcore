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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

interface OptimizedAdminLoadingInterface
{
    /**
     * e.g. load relations directly from relations table and if necessary additional data
     * (like object attributes or meta data) asynchronously when the UI is ready
     *
     */
    public function isOptimizedAdminLoading(): bool;
}
