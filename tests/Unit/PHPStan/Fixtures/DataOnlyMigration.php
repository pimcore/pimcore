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

namespace Pimcore\Tests\Unit\PHPStan\Fixtures;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class DataOnlyMigration extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM foo');
        $this->addSql('CREATE TEMPORARY TABLE tmp (id INT)');
        $this->connection->executeStatement('SET foreign_key_checks = 0');
        $schema->getTable('foo')->getColumn('bar');
    }
}
