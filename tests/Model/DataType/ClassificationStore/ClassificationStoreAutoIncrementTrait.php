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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore\Db;

/**
 * Restores the AUTO_INCREMENT of the classification store configuration tables.
 *
 * A test which creates and deletes rows otherwise leaves the counter raised, and the
 * fixtures of AbstractClassificationStoreTest are looked up through the default store id.
 *
 * @internal
 */
trait ClassificationStoreAutoIncrementTrait
{
    private const AUTO_INCREMENT_TABLES = [
        'classificationstore_stores',
        'classificationstore_collections',
        'classificationstore_groups',
        'classificationstore_keys',
    ];

    protected function restoreClassificationStoreAutoIncrement(): void
    {
        $db = Db::get();

        foreach (self::AUTO_INCREMENT_TABLES as $table) {
            $next = (int) $db->fetchOne('SELECT COALESCE(MAX(`id`), 0) + 1 FROM `' . $table . '`');
            $db->executeStatement('ALTER TABLE `' . $table . '` AUTO_INCREMENT = ' . $next);
        }
    }
}
