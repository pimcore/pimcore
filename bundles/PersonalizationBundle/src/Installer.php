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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\PersonalizationBundle;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class Installer extends SettingsStoreAwareInstaller
{
    protected const USER_PERMISSIONS_CATEGORY = 'Pimcore Personalization Bundle';

    protected const USER_PERMISSIONS = [
        'targeting'
    ];

    public function install(): void
    {
        $this->installDatabaseTable();
        $this->addUserPermission();
        parent::install();
    }

    public function uninstall(): void
    {
        // Cleanup should be done manually

        $style = new SymfonyStyle(new StringInput(''), new ConsoleOutput());

        if(!($style->confirm('<comment>[WARNING]</comment> When Uninstalling the bundle, the data types \'Target Group\' and \'Target Group Multiselect\' would be removed. So, if you are using data objects with any of these types, please remove the data type from the class manually before uninstalling. Do you want to continue the uninstall?',false))){
            exit;
        }

        $this->uninstallDatabaseTable();
        $this->removeUserPermission();
        parent::uninstall();
    }

    private function addUserPermission(): void
    {
        $db = \Pimcore\Db::get();

        foreach(self::USER_PERMISSIONS as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
                $db->quoteIdentifier('category') => self::USER_PERMISSIONS_CATEGORY,
            ]);
        }
    }

    private function removeUserPermission(): void
    {
        $db = \Pimcore\Db::get();

        foreach(self::USER_PERMISSIONS as $permission) {
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

        foreach($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath . $fileName);
            $db->executeQuery($statement);
        }
    }

    private function uninstallDatabaseTable(): void
    {
        $sqlPath = __DIR__ . '/Resources/uninstall/';
        $sqlFileNames = ['uninstall.sql'];
        $db = \Pimcore\Db::get();

        foreach($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath . $fileName);
            $db->executeQuery($statement);
        }
    }
}
