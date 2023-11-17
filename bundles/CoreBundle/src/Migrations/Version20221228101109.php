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

final class Version20221228101109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'In case the custom reports permissions already exists, mark the CustomReportsBundle as installed';
    }

    public function up(Schema $schema): void
    {
        if (!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\CustomReportsBundle\\PimcoreCustomReportsBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\CustomReportsBundle\\PimcoreCustomReportsBundle', true, SettingsStore::TYPE_BOOLEAN, 'pimcore');
        }

        // updating description  of permissions
        $this->addSql("UPDATE `users_permission_definitions` SET `category` = 'Pimcore Custom Reports Bundle' WHERE `key` IN('reports', 'reports_config')");

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\CustomReportsBundle\\PimcoreCustomReportsBundle', 'pimcore'),
            'Please make sure to enable the Pimcore\\Bundle\\CustomReportsBundle\\PimcoreCustomReportsBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {
        if (SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\CustomReportsBundle\\PimcoreCustomReportsBundle', 'pimcore')) {
            SettingsStore::delete('BUNDLE_INSTALLED__Pimcore\\Bundle\\CustomReportsBundle\\PimcoreCustomReportsBundle', 'pimcore');
        }

        // restoring the permission
        $this->addSql("UPDATE `users_permission_definitions` SET `category` = '' WHERE `key` IN('reports', 'reports_config')");

        $this->write('Please deactivate the Pimcore\\Bundle\\CustomReportsBundle\\PimcoreCustomReportsBundle manually in config/bundles.php');
    }
}
