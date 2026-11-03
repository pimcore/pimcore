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
 * Adds a composite (parentId, type, published) index to the objects table.
 *
 * The child checks of the object DAO (hasChildren(), hasSiblings(), getChildAmount()) filter by
 * exactly these columns. With only the single-column parentId and published indexes, MySQL may
 * answer `WHERE parentId = ? AND published = 1 AND type IN (...) LIMIT 1` by scanning the
 * published index, which matches nearly every row of the table. The composite index covers these
 * queries, so each check becomes a single index lookup.
 */
final class Version20261102120000 extends AbstractMigration
{
    private const INDEX_NAME = 'parentId_type_published';

    public function getDescription(): string
    {
        return 'Add a composite (parentId, type, published) index to the objects table';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('objects')->hasIndex(self::INDEX_NAME)) {
            $this->addSql('ALTER TABLE `objects` ADD INDEX `' . self::INDEX_NAME . '` (`parentId`, `type`, `published`)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('objects')->hasIndex(self::INDEX_NAME)) {
            $this->addSql('ALTER TABLE `objects` DROP INDEX `' . self::INDEX_NAME . '`');
        }
    }
}
