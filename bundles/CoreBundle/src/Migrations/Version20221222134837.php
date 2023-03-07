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
 * Staticroutes will be installed by default
 */
final class Version20221222134837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Install static routes bundle by default';
    }

    public function up(Schema $schema): void
    {
        if (!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\StaticRoutesBundle\\PimcoreStaticRoutesBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\StaticRoutesBundle\\PimcoreStaticRoutesBundle', true, SettingsStore::TYPE_BOOLEAN, 'pimcore');
        }

        // updating description  of permissions
        $this->addSql("UPDATE `users_permission_definitions` SET `category` = 'Pimcore Static Routes Bundle' WHERE `key` = 'routes'");

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\StaticRoutesBundle\\PimcoreStaticRoutesBundle', 'pimcore'),
            'Please make sure to enable the Pimcore\\Bundle\\StaticRoutesBundle\\PimcoreStaticRoutesBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {
        if (SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\StaticRoutesBundle\\PimcoreStaticRoutesBundle', 'pimcore')) {
            SettingsStore::delete('BUNDLE_INSTALLED__Pimcore\\Bundle\\StaticRoutesBundle\\PimcoreStaticRoutesBundle', 'pimcore');
        }

        // restoring the permission
        $this->addSql("UPDATE `users_permission_definitions` SET `category` = '' WHERE `key` = 'routes'");

        $this->write('Please deactivate the Pimcore\\Bundle\\StaticRoutesBundle\\PimcoreStaticRoutesBundle manually in config/bundles.php');
    }
}
