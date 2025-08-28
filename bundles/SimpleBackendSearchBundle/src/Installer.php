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

namespace Pimcore\Bundle\SimpleBackendSearchBundle;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

/**
 * @internal
 */
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

    private function installDatabaseTable(): void
    {
        $sqlPath = (__DIR__ . '/Resources/install/');
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
