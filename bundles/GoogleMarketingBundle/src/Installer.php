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

namespace Pimcore\Bundle\GoogleMarketingBundle;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    const USER_PERMISSIONS = [
        'reports',
        'system_settings'
    ];
    protected function addPermissions(): void
    {
        $db = \Pimcore\Db::get();

        /**
         * The following code is here for backwards compatibility reason.
         * If the permissions are already installed, the installer should not generate
         * any further errors and only add permissions which
         */
        $permissionsString = array_map(function ($permission) {
            return '\'' . $permission . '\'';
        }, self::USER_PERMISSIONS);

        $alreadyInstalled = $db->fetchAllAssociative('select `key` from users_permission_definitions where `key` in ('. implode(',', $permissionsString) .');');
        $columns = array_column($alreadyInstalled, 'key');

        $remainingPermissions = array_diff(self::USER_PERMISSIONS, $columns);

        foreach ($remainingPermissions as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
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

}
