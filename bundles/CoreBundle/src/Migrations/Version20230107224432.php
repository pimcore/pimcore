<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Tool\SettingsStore;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230107224432 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Marks the XliffBundle as installed and grants XLIFF import/export permissions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT IGNORE INTO `users_permission_definitions` (`key`, `category`) VALUES ('xliff_import_export', 'Pimcore Xliff Bundle')");

        // Append to the comma separated list whenever the permissions text field has 'translation' but not already xliff_import_export
        $this->addSql('UPDATE users SET permissions = CONCAT(permissions, \',xliff_import_export\') WHERE `permissions` REGEXP \'(?:^|,)translations(?:$|,)\'');

        if (!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle', true, 'bool', 'pimcore');
        }

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle', 'pimcore'),
            'Please make sure to enable the Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle manually in config/bundles.php'
        );

    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)xliff_import_export(?:^|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)xliff_import_export(?:$|,)\'');

        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'xliff_import_export'");

        if (SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle', 'pimcore')) {
            SettingsStore::delete('BUNDLE_INSTALLED__Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle', 'pimcore');
        }
    }
}
