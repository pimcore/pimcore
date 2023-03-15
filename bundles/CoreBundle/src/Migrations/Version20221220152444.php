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
 * Set installed if not set yet
 */
final class Version20221220152444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Checks if Web2Print is installed in Settingsstore';
    }

    public function up(Schema $schema): void
    {
        $enableBundle = false;
        if (!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\WebToPrintBundle\\PimcoreWebToPrintBundle', 'pimcore')) {
            // remove enableInDefaultView setting
            $settings = SettingsStore::get('web_to_print', 'pimcore_web_to_print');
            if ($settings) {
                $data = json_decode($settings->getData(), true);

                if (isset($data['enableInDefaultView'])) {
                    $enableBundle = $data['enableInDefaultView'];
                    unset($data['enableInDefaultView']);
                    $data = json_encode($data);
                    SettingsStore::set('web_to_print', $data, SettingsStore::TYPE_STRING, 'pimcore_web_to_print');
                }
            }

            // updating description  of permissions
            $this->addSql("UPDATE `users_permission_definitions` SET `category` = 'Pimcore Web2Print Bundle' WHERE `key` = 'web2print_settings'");

            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\WebToPrintBundle\\PimcoreWebToPrintBundle', $enableBundle, SettingsStore::TYPE_BOOLEAN, 'pimcore');
        }

        $this->warnIf(
            $enableBundle,
            'Please make sure to enable the BUNDLE_INSTALLED__Pimcore\\Bundle\\WebToPrintBundle\\PimcoreWebToPrintBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {
        // restoring the permission
        $this->addSql("UPDATE `users_permission_definitions` SET `category` = '' WHERE `key` = 'web2print_settings'");
        // restoring the enableInDefaultView might be with wrong value
        SettingsStore::delete('BUNDLE_INSTALLED__Pimcore\\Bundle\\WebToPrintBundle\\PimcoreWebToPrintBundle', 'pimcore');
        $settings = SettingsStore::get('web_to_print', 'pimcore_web_to_print');

        if ($settings) {
            $data = json_decode($settings->getData(), true);
            if (!isset($data['enableInDefaultView'])) {
                // we do not know the original value so we set it to false
                $data['enableInDefaultView'] = false;
                $data = json_encode($data);
                SettingsStore::set('web_to_print', $data, SettingsStore::TYPE_STRING, 'pimcore_web_to_print');
            }
        }
        // always warn
        $this->warnIf(
            true,
            "Please check your Web2Print settings and permissions. The 'Enable Web2Print documents in default documents view' will be disabled"
        );
        $this->write('Please deactivate the BUNDLE_INSTALLED__Pimcore\\Bundle\\WebToPrintBundle\\PimcoreWebToPrintBundle manually in config/bundles.php');
    }
}
