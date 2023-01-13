<?php

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
