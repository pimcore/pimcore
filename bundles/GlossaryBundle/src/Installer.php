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

namespace Pimcore\Bundle\GlossaryBundle;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

/**
 * @internal
 */
class Installer extends SettingsStoreAwareInstaller
{
    public function install()
    {
        $this->installDatabaseTable();
        parent::install();
    }

    public function uninstall() {
        $this->uninstallDatabaseTable();
        parent::uninstall();
    }

    private function installDatabaseTable()
    {
        $sqlPath = __DIR__ . '/Resources/install/';
        $sqlFileNames = ['install.sql'];
        $db = \Pimcore\Db::get();

        foreach ($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath.$fileName);
            $db->executeQuery($statement);
        }
    }

    private function uninstallDatabaseTable()
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
