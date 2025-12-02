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
final class Version20210505093841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'WebDAV locks with database backend instead of file-based';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS webdav_locks (
                id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                owner VARCHAR(100),
                timeout INTEGER UNSIGNED,
                created INTEGER,
                token VARBINARY(100),
                scope TINYINT,
                depth TINYINT,
                uri VARBINARY(1000),
                INDEX(token),
                INDEX(uri(100))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS webdav_locks');
    }
}
