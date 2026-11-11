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

use function bin2hex;
use function random_bytes;

/**
 * Stores an unusable password for every service user that has none, the value
 * \Pimcore\Model\User::save() for a service user writes on every save, so that
 * \Pimcore\Tool\Authentication::isValidUser() keeps treating a saved, active service user like a user.
 * Service users saved after the change already carry such a value; the prefix marks it as unusable
 * (it is no password hash, so no password ever verifies against it).
 */
final class Version20261111090000 extends AbstractMigration
{
    /** the same prefix as the private \Pimcore\Model\User::UNUSABLE_PASSWORD_PREFIX */
    private const UNUSABLE_PASSWORD_PREFIX = '!serviceuser-';

    public function getDescription(): string
    {
        return 'Store an unusable password for service users that have none';
    }

    public function up(Schema $schema): void
    {
        $ids = $this->connection->fetchFirstColumn(
            "SELECT id FROM users WHERE `type` = 'serviceuser' AND (password IS NULL OR password = '')"
        );

        foreach ($ids as $id) {
            $this->addSql(
                'UPDATE users SET password = :password WHERE id = :id',
                ['password' => self::UNUSABLE_PASSWORD_PREFIX . bin2hex(random_bytes(32)), 'id' => (int) $id]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE users SET password = NULL WHERE `type` = 'serviceuser' AND password LIKE :prefix",
            ['prefix' => self::UNUSABLE_PASSWORD_PREFIX . '%']
        );
    }
}
