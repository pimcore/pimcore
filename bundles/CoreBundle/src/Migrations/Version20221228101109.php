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
use Pimcore\Bundle\CustomReportsBundle\PimcoreCustomReportsBundle;
use Pimcore\Db;
use Pimcore\Model\Tool\SettingsStore;

final class Version20221228101109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'In case the custom reports permissions already exists, mark the CustomReportsBundle as installed';
    }

    public function isInstalled(): bool
    {
        $db = Db::get();
        $cnt = $db->fetchAllAssociative("SELECT count(`key`) as permission_count from users_permission_definitions WHERE `key` = 'reports' or `key` = 'reports_config'");
        if ($cnt[0]['permission_count'] === 2) {
            return true;
        }

        return false;
    }

    public function up(Schema $schema): void
    {
        if ($this->isInstalled()) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\CustomReportsBundle\\PimcoreCustomReportsBundle', true, 'bool', 'pimcore');
        }

        $this->warnIf(
            $this->isInstalled(),
            sprintf('Please make sure to enable the %s manually in config/bundles.php', PimcoreCustomReportsBundle::class)
        );
    }

    public function down(Schema $schema): void
    {
        $this->write(sprintf('Please deactivate the %s manually in config/bundles.php', PimcoreCustomReportsBundle::class));
    }
}
