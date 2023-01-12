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

namespace Pimcore\Bundle\FileExplorerBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Tool\SettingsStore;

/**
 * Install file explorer permissions
 */
final class Version20230112205000 extends AbstractMigration
{
    const PERMISSIONS = ['fileexplorer'];
    const PERMISSIONS_TABLE_NAME = 'users_permission_definitions';

    public function up(Schema $schema): void
    {
        $db = \Pimcore\Db::get();
        $query = sprintf('INSERT INTO `%s` (`key`, `category`) VALUES ', self::PERMISSIONS_TABLE_NAME);

        foreach (self::PERMISSIONS as $permission) {
            $query .= sprintf('(\'%s\', \'%s\'), ', $permission, '');
        }

        $query = trim($query, ', ');
        $query .= ';';
        $db->executeQuery($query);
    }

    public function down(Schema $schema): void
    {
        $db = \Pimcore\Db::get();
        $query = sprintf('DELETE FROM `%s` WHERE `key` IN (%s);', self::PERMISSIONS_TABLE_NAME, implode(',', self::PERMISSIONS));
        $db->executeQuery($query);
    }
}
