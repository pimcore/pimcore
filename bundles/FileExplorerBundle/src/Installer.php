<?php

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

namespace Pimcore\Bundle\FileExplorerBundle;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    const USER_PERMISSIONS = [
        'fileexplorer',
    ];

    const USE_PERMISSION_CATEGORY = 'Pimcore File Explorer Bundle';

    public function install(): void
    {
        $this->addPermissions();
        parent::install();
    }

    public function uninstall(): void
    {
        $this->removePermissions();
        parent::uninstall();
    }

    protected function addPermissions(): void
    {
        $db = \Pimcore\Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
                $db->quoteIdentifier('category') => self::USE_PERMISSION_CATEGORY,
            ]);
        }
    }

    protected function removePermissions(): void
    {
        $db = \Pimcore\Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->delete('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
            ]);
        }
    }
}
