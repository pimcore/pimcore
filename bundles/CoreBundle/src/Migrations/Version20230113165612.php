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
 * UUID bundle will be enabled by default will be enabled by default
 */
final class Version20230113165612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable UUID bundle if if not enabled already';
    }

    public function up(Schema $schema): void
    {
        if (!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\UuidBundle\\PimcoreUuidBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\UuidBundle\\PimcoreUuidBundle', true, SettingsStore::TYPE_BOOLEAN, 'pimcore');
        }

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\UuidBundle\\PimcoreUuidBundle', 'pimcore'),
            'Please make sure to enable the Pimcore\\Bundle\\UuidBundle\\PimcoreUuidBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {
        if (SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\UuidBundle\\PimcoreUuidBundle', 'pimcore')) {
            SettingsStore::delete('BUNDLE_INSTALLED__Pimcore\\Bundle\\UuidBundle\\PimcoreUuidBundle', 'pimcore');
        }
        $this->write('Please deactivate the Pimcore\\Bundle\\UuidBundle\\PimcoreUuidBundle manually in config/bundles.php');
    }
}
