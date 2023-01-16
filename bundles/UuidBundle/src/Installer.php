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

namespace Pimcore\Bundle\UuidBundle;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    public function install(): void
    {
        $this->installDatabaseTable();
        parent::install();
    }

    public function uninstall(): void
    {
        $this->uninstallDatabaseTable();
        parent::uninstall();
    }

    private function runSqlQueries(array $sqlFileNames): void
    {
        $sqlPath = __DIR__ . '/Resources/';
        $db = \Pimcore\Db::get();

        foreach ($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath.$fileName);
            $db->executeQuery($statement);
        }
    }

    protected function installDatabaseTable(): void
    {
        $this->runSqlQueries(['install/install.sql']);
    }

    protected function uninstallDatabaseTable(): void
    {
        $this->runSqlQueries(['uninstall/uninstall.sql']);
    }
}
