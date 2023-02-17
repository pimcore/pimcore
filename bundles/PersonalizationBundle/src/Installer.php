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

namespace Pimcore\Bundle\PersonalizationBundle;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class Installer extends SettingsStoreAwareInstaller
{
    protected const USER_PERMISSIONS_CATEGORY = 'Pimcore Personalization Bundle';

    protected const USER_PERMISSIONS = [
        'targeting',
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

        $output = new ConsoleOutput();
        $style = new SymfonyStyle(new StringInput(''), $output);

        if (!($style->confirm(
            "<comment>[WARNING]</comment> Before Uninstalling the bundle, 'Target Group' references must be removed from DataObject classes,\n" .
            "Custom services and Ecommerce Pricing Rules manually.\n\n" .
            'Do you want to continue the uninstall?', false))) {
            $output->writeln('<info>Uninstall Aborted.</info>');
            exit;
        }

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
            $statement = file_get_contents($sqlPath . $fileName);
            $db->executeQuery($statement);
        }
    }

    private function uninstallDatabaseTable(): void
    {
        $sqlPath = __DIR__ . '/Resources/uninstall/';
        $sqlFileNames = ['uninstall.sql'];
        $db = \Pimcore\Db::get();

        foreach ($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath . $fileName);
            $db->executeQuery($statement);
        }
    }
}
