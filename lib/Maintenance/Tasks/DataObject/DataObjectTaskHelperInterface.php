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

namespace Pimcore\Maintenance\Tasks\DataObject;

/**
 * @internal
 */
interface DataObjectTaskHelperInterface
{
    public function getCollectionNames(string $dir): array;

    public function cleanupTable(
        string $tableName,
        string $classId,
        bool $isLocalized = true
    ): void;
}
