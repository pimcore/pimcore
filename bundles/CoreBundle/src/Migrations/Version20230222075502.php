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
final class Version20230222075502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Install google marketing bundle by default';
    }

    public function up(Schema $schema): void
    {
        if (!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\GoogleMarketingBundle\\PimcoreGoogleMarketingBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\GoogleMarketingBundle\\PimcoreGoogleMarketingBundle', true, SettingsStore::TYPE_BOOLEAN, 'pimcore');
        }

        $this->addSql("INSERT IGNORE INTO `users_permission_definitions` (`key`, `category`) VALUES ('google_marketing', 'Pimcore Google Marketing Bundle')");

        // Append to the comma separated list whenever the permissions text field has 'system_settings' but not already google_marketing
        $this->addSql('UPDATE users SET permissions = CONCAT(permissions, \',google_marketing\') WHERE `permissions` REGEXP \'(?:^|,)system_settings(?:$|,)\'');

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\GoogleMarketingBundle\\PimcoreGoogleMarketingBundle', 'pimcore'),
            'Please make sure to enable the Pimcore\\Bundle\\GoogleMarketingBundle\\PimcoreGoogleMarketingBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {
        if (SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\GoogleMarketingBundle\\PimcoreGoogleMarketingBundle', 'pimcore')) {
            SettingsStore::delete('BUNDLE_INSTALLED__Pimcore\\Bundle\\GoogleMarketingBundle\\PimcoreGoogleMarketingBundle', 'pimcore');
        }

        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)google_marketing(?:^|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)google_marketing(?:$|,)\'');

        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'google_marketing'");

        $this->write('Please deactivate the Pimcore\\Bundle\\GoogleMarketingBundle\\PimcoreGoogleMarketingBundle manually in config/bundles.php');
    }
}
