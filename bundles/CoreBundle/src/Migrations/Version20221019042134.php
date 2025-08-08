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
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221019042134 extends AbstractMigration
{
    const TABLE_NAME = 'users_permission_definitions';

    const PERMISSION = 'plugins';

    public function getDescription(): string
    {
        return 'Remove plugin permission from  ' . self::TABLE_NAME . ' table!';
    }

    public function up(Schema $schema): void
    {
        $query = 'DELETE FROM %s WHERE `key` = \'%s\';';
        $this->addSql(sprintf($query, self::TABLE_NAME, self::PERMISSION));
    }

    public function down(Schema $schema): void
    {
        $query = 'INSERT INTO %s(`key`) VALUES (\'%s\')';
        $this->addSql(sprintf($query, self::TABLE_NAME, self::PERMISSION));
    }
}
