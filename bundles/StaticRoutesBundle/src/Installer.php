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

namespace Pimcore\Bundle\StaticRoutesBundle;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Pimcore\Model\Tool\SettingsStore;

/**
 * @internal
 */
class Installer extends SettingsStoreAwareInstaller
{
    protected const SETTINGS_STORE_SCOPE = 'pimcore_staticroutes';

    protected const USER_PERMISSION_CATEGORY = 'Pimcore Static Routes Bundle';

    protected const USER_PERMISSIONS = [
        'routes',
    ];

    public function install(): void
    {
        $this->addUserPermission();
        parent::install();
    }

    public function uninstall(): void
    {
        $this->removeUserPermission();
        $this->removeRoutesFromSettingsStore();
        parent::uninstall();
    }

    private function addUserPermission(): void
    {
        $db = \Pimcore\Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
                $db->quoteIdentifier('category') => self::USER_PERMISSION_CATEGORY,
            ]);
        }
    }

    private function removeUserPermission(): void
    {
        $db = \Pimcore\Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->delete('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
            ]);
        }
    }

    private function removeRoutesFromSettingsStore(): void
    {
        $staticRoutes = SettingsStore::getIdsByScope(self::SETTINGS_STORE_SCOPE);
        if (!empty($staticRoutes)) {
            foreach ($staticRoutes as $staticRoute) {
                SettingsStore::delete($staticRoute, self::SETTINGS_STORE_SCOPE);
            }
        }
    }
}
