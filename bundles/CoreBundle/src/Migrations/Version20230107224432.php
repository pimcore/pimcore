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
        if (!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle', true, SettingsStore::TYPE_BOOLEAN, 'pimcore');
        }

        // inserting new permission
        $this->addSql("INSERT IGNORE INTO `users_permission_definitions` (`key`, `category`) VALUES ('xliff_import_export', 'Pimcore Xliff Bundle')");

        // Append to the comma separated list whenever the permissions text field has 'translation' but not already xliff_import_export
        $this->addSql('UPDATE users SET permissions = CONCAT(permissions, \',xliff_import_export\') WHERE `permissions` REGEXP \'(?:^|,)translations(?:$|,)\'');

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle', 'pimcore'),
            'Please make sure to enable the Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {
        if (SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle', 'pimcore')) {
            SettingsStore::delete('BUNDLE_INSTALLED__Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle', 'pimcore');
        }

        // removing permission
        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'xliff_import_export'");

        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)xliff_import_export(?:^|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)xliff_import_export(?:$|,)\'');

        $this->write('Please deactivate the Pimcore\\Bundle\\XliffBundle\\PimcoreXliffBundle manually in config/bundles.php');
    }
}
