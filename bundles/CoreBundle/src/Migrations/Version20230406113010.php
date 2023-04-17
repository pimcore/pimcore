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

final class Version20230406113010 extends AbstractMigration
{
    protected const USER_PERMISSION_CATEGORY = 'Pimcore Newsletter Bundle';

    protected const USER_PERMISSION = 'newsletters';

    public function getDescription(): string
    {
        return 'Install newsletter bundle by default if it was in use';
    }

    public function up(Schema $schema): void
    {
        $db = \Pimcore\Db::get();

        // check if there are any existing newsletters, if found, it was in use and we activate bundle by default
        $newsletters = $db->fetchFirstColumn('SELECT id FROM documents WHERE type = ?', ['newsletter']);
        if (!$newsletters) {
            return;
        }

        $this->addSql(
            sprintf(
                'INSERT IGNORE INTO `users_permission_definitions` (`key`, `category`) VALUES (\'%s\', \'%s\');',
                self::USER_PERMISSION, self::USER_PERMISSION_CATEGORY
            )
        );

        $this->addSql('UPDATE users SET permissions = CONCAT(permissions, \',' . self::USER_PERMISSION . '\') WHERE `permissions` REGEXP \'(?:^|,)emails(?:$|,)\'');

        if (!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle', true, SettingsStore::TYPE_BOOLEAN, 'pimcore');
        }

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle', 'pimcore'),
            'Please make sure to enable the BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)' . self::USER_PERMISSION . '(?:^|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)' . self::USER_PERMISSION . '(?:$|,)\'');

        $this->addSql(sprintf('DELETE FROM users_permission_definitions WHERE `key` = \'%s\';', self::USER_PERMISSION));

        if (SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle', 'pimcore')) {
            SettingsStore::delete('BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle', 'pimcore');
        }

        $this->write('Please deactivate the BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle manually in config/bundles.php');
    }
}
