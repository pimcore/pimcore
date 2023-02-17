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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Tool\SettingsStore;

final class Version20230125164101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mark the PersonalizationBundle as installed';
    }

    public function up(Schema $schema): void
    {
        if(!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\PersonalizationBundle\\PimcorePersonalizationBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\PersonalizationBundle\\PimcorePersonalizationBundle', true, 'bool', 'pimcore');
        }

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\PersonalizationBundle\\PimcorePersonalizationBundle', 'pimcore'),
            'Please make sure to enable the Pimcore\\Bundle\\PersonalizationBundle\\PimcorePersonalizationBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {

    }
}
