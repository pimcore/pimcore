<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Tool\SettingsStore;

final class Version20230110120552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Marks the SystemInfoBundle as installed';
    }

    public function up(Schema $schema): void
    {
        if(!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\SystemInfoBundle\\PimcoreSystemInfoBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\SystemInfoBundle\\PimcoreSystemInfoBundle', true, 'bool', 'pimcore');
        }

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\SystemInfoBundle\\PimcoreSystemInfoBundle', 'pimcore'),
            'Please make sure to enable the Pimcore\\Bundle\\SystemInfoBundle\\PimcoreSystemInfoBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {
        //do nothing
    }
}
