<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CustomReportsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Bundle\ApplicationLoggerBundle\PimcoreApplicationLoggerBundle;
use Pimcore\Db;
use Pimcore\Model\Tool\SettingsStore;

final class Migration20221229110804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'In case the application log table already exists, mark the CustomReportsBundle as installed';
    }

    public function isInstalled(Schema $schema): bool
    {
        return $schema->hasTable('application_log');
    }

    public function up(Schema $schema): void
    {
        if ($this->isInstalled()) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\ApplicationLoggerBundle\\PimcoreApplicationLoggerBundle', true, 'bool', 'pimcore');
        }

        $this->warnIf(
            $this->isInstalled(),
            sprintf('Please make sure to enable the %s manually in config/bundles.php', PimcoreApplicationLoggerBundle::class)
        );
    }

    public function down(Schema $schema): void
    {
        $this->write(sprintf('Please deactivate the %s manually in config/bundles.php', PimcoreCustomReportsBundle::class));
    }
}
