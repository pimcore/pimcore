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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Tool\SettingsStore;

/**
 * Granting Word export permission for translation permission!
 */
final class Version20230111074323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Grant Word export permission to those who already had "translation" permission, due Word export functionalities being moved out to be Standalone from Core Translation';
    }

    public function up(Schema $schema): void
    {
        if (!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\WordExportBundle\\PimcoreWordExportBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\WordExportBundle\\PimcoreWordExportBundle', true, SettingsStore::TYPE_BOOLEAN, 'pimcore');
        }

        // Append to the comma separated list whenever the permissions text field has 'translation' but not already word_export
        $this->addSql("INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES ('word_export', 'Pimcore Word Export Bundle') ON DUPLICATE KEY UPDATE `category` = 'Pimcore Word Export Bundle';");

        $this->addSql('UPDATE `users` SET `permissions`=CONCAT(`permissions`, \',word_export\') WHERE `permissions` REGEXP \'(?:^|,)translations(?:$|,)\'');

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\WordExportBundle\\PimcoreWordExportBundle', 'pimcore'),
            'Please make sure to enable the Pimcore\\Bundle\\WordExportBundle\\PimcoreWordExportBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {
        if (SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\WordExportBundle\\PimcoreWordExportBundle', 'pimcore')) {
            SettingsStore::delete('BUNDLE_INSTALLED__Pimcore\\Bundle\\WordExportBundle\\PimcoreWordExportBundle', 'pimcore');
        }

        // Replace to remove permission when the comma is suffixed (eg. first of the list or any order)
        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)word_export(?:^|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)word_export(?:$|,)\'');

        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'word_export'");

        $this->write('Please deactivate the Pimcore\\Bundle\\WordExportBundle\\PimcoreWordExportBundle manually in config/bundles.php');
    }
}
