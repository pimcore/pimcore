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
use Pimcore\Bundle\ApplicationLoggerBundle\PimcoreApplicationLoggerBundle;
use Pimcore\Db;
use Pimcore\Model\Tool\SettingsStore;

final class Version20230120111111 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'In case the application logger permissions already exists, mark the ApplicationLoggerBundle as installed';
    }

    public function isInstalled(): bool
    {
        $db = Db::get();
        $cnt = $db->fetchAllAssociative("SELECT count(`key`) as permission_count from users_permission_definitions WHERE `key` = 'application_logging'");
        if ($cnt[0]['permission_count'] === 1) {
            return true;
        }

        return false;
    }

    public function up(Schema $schema): void
    {
        if ($this->isInstalled()) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\ApplicationLoggerBundle\\PimcoreApplicationLoggerBundle', true, 'bool', 'pimcore');
        }

        $this->warnIf(
            $this->isInstalled(),
            sprintf('Please make sure to enable the %s manually in config/bundles.php', PimcoreApplicationLoggerBundle::class)
        );
    }

    public function down(Schema $schema): void
    {
        $this->write(sprintf('Please deactivate the %s manually in config/bundles.php', PimcoreApplicationLoggerBundle::class));
    }
}
