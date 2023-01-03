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
    }

    public function uninstall()
    {
        $this->revokePermissions();
    }

    protected function addPermissions()
    {
        $db = \Pimcore\Db::get();
        foreach (self::USER_PERMISSIONS as $permission) {
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
