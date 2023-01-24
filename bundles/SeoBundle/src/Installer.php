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

namespace Pimcore\Bundle\SeoBundle;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    protected const USER_PERMISSIONS_CATEGORY = 'Pimcore Seo Bundle';

    protected const USER_PERMISSIONS = [
        'robots.txt',
        'seo_document_editor',
        'http_errors',
    ];

    public function install(): void
    {
        $this->installDatabaseTable();
        $this->addUserPermission();
        parent::install();
    }

    public function uninstall(): void
    {
        $this->uninstallDatabaseTable();
        $this->removeUserPermission();
        parent::uninstall();
    }

    private function addUserPermission(): void
    {
        $db = \Pimcore\Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
                $db->quoteIdentifier('category') => self::USER_PERMISSIONS_CATEGORY,
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

    private function installDatabaseTable(): void
    {
        $sqlPath = __DIR__ . '/Resources/install/';
        $sqlFileNames = ['install.sql'];
        $db = \Pimcore\Db::get();

        foreach ($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath.$fileName);
            $db->executeQuery($statement);
        }
    }

    private function uninstallDatabaseTable(): void
    {
        $sqlPath = __DIR__ . '/Resources/uninstall/';
        $sqlFileNames = ['uninstall.sql'];
        $db = \Pimcore\Db::get();

        foreach ($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath.$fileName);
            $db->executeQuery($statement);
        }
    }
}
