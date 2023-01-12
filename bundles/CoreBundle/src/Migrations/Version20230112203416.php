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
use Pimcore\Model\Tool\SettingsStore;

/**
 * File Explorer will be disabled by default
 */
final class Version20230112203416 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        if (SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\FileExplorerBundle\\PimcoreFileExplorerBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\FileExplorerBundle\\PimcoreFileExplorerBundle', false, 'bool', 'pimcore');
        }

        $db = \Pimcore\Db::get();
        $permissionsTable = `users_permission_definitions`;

        $query = sprintf('DELETE FROM `%s` WHERE `key` IN (%s);', $permissionsTable, implode(',', ['fileexplorer']));
        $db->executeQuery($query);
    }
}
