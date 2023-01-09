<?php

namespace Pimcore\Bundle\FileExplorerBundle;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    const USER_PERMISSIONS = [
        'fileexplorer'
    ];

    public function install()
    {
        $this->addPermissions();
        parent::install();
    }

    public function uninstall()
    {
        $this->revokePermissions();
        parent::uninstall();
    }

    protected function addPermissions()
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

    protected function revokePermissions()
    {
        $db = \Pimcore\Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->delete('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
            ]);
        }
    }
}
