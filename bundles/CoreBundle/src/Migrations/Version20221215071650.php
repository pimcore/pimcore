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
 * Checking if glossary tables already exist
 */
final class Version20221215071650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'In case the glossary table already exists, it marks the GlossaryBundle as installed';
    }

    public function up(Schema $schema): void
    {
        if (!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\GlossaryBundle\\PimcoreGlossaryBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\GlossaryBundle\\PimcoreGlossaryBundle', true, SettingsStore::TYPE_BOOLEAN, 'pimcore');
        }

        // updating description  of permissions
        $this->addSql("UPDATE `users_permission_definitions` SET `category` = 'Pimcore Glossary Bundle' WHERE `key` = 'glossary'");

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\GlossaryBundle\\PimcoreGlossaryBundle', 'pimcore'),
            'Please make sure to enable the Pimcore\\Bundle\\GlossaryBundle\\PimcoreGlossaryBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {
        if (SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\GlossaryBundle\\PimcoreGlossaryBundle', 'pimcore')) {
            SettingsStore::delete('BUNDLE_INSTALLED__Pimcore\\Bundle\\GlossaryBundle\\PimcoreGlossaryBundle', 'pimcore');
        }

        // restoring the permission
        $this->addSql("UPDATE `users_permission_definitions` SET `category` = '' WHERE `key` = 'glossary'");

        $this->write('Please deactivate the Pimcore\\Bundle\\GlossaryBundle\\PimcoreGlossaryBundle manually in config/bundles.php');
    }
}
