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
