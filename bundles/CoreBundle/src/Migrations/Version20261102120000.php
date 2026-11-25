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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Replaces the single-column parentId index of the objects table with a composite
 * (parentId, type, published) index.
 *
 * The child checks of the object DAO (hasChildren(), hasSiblings(), getChildAmount()) filter by
 * exactly these columns. With the single-column parentId and published indexes, MySQL 8 answers
 * `WHERE parentId = ? AND published = 1 AND type IN (...) LIMIT 1` for a parent with many children
 * with an index-merge intersection of the two, whose row estimate then sends it to a scan of the
 * published index or of the whole table. Adding the composite index alone does not prevent that:
 * the intersection stays cheaper by the optimizer's estimate. Without the single-column parentId
 * index it cannot be built, and the composite index serves every lookup by parentId as well.
 *
 * @internal
 */
final class Version20261102120000 extends AbstractMigration
{
    private const INDEX_NAME = 'parentId_type_published';

    private const REPLACED_INDEX_NAME = 'parentId';

    public function getDescription(): string
    {
        return 'Replace the parentId index of the objects table with a composite (parentId, type, published) index';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('objects');

        $changes = [];
        if (!$table->hasIndex(self::INDEX_NAME)) {
            $changes[] = 'ADD INDEX `' . self::INDEX_NAME . '` (`parentId`, `type`, `published`)';
        }
        if ($table->hasIndex(self::REPLACED_INDEX_NAME)) {
            $changes[] = 'DROP INDEX `' . self::REPLACED_INDEX_NAME . '`';
        }

        // One statement, so lookups by parentId are never left without an index.
        if ($changes) {
            $this->addSql('ALTER TABLE `objects` ' . implode(', ', $changes));
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('objects');

        $changes = [];
        if (!$table->hasIndex(self::REPLACED_INDEX_NAME)) {
            $changes[] = 'ADD INDEX `' . self::REPLACED_INDEX_NAME . '` (`parentId`)';
        }
        if ($table->hasIndex(self::INDEX_NAME)) {
            $changes[] = 'DROP INDEX `' . self::INDEX_NAME . '`';
        }

        if ($changes) {
            $this->addSql('ALTER TABLE `objects` ' . implode(', ', $changes));
        }
    }
}
