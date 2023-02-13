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
use Pimcore\Db;
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
        if(!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\WebToPrintBundle\\PimcoreWebToPrintBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\WebToPrintBundle\\PimcoreWebToPrintBundle', true, 'bool', 'pimcore');
            // updating description
            $db = Db::get();
            $db->update('users_permission_definitions', ['category' => 'Pimcore Web2Print Bundle'], ['`key`' => 'web2print_settings']);

            // remove enableInDefaultView setting
            $settings = SettingsStore::get('web_to_print', 'pimcore_web_to_print');
            if($settings) {
                $data = json_decode($settings->getData(), true);

                if(isset($data['enableInDefaultView'])) {
                    unset($data['enableInDefaultView']);
                    $data = json_encode($data);
                    SettingsStore::set('web_to_print', $data,'string', 'pimcore_web_to_print');
                }
            }

        }

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\WebToPrintBundle\\PimcoreWebToPrintBundle', 'pimcore'),
           'Please make sure to enable the BUNDLE_INSTALLED__Pimcore\\Bundle\\WebToPrintBundle\\PimcoreWebToPrintBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {
        // restoring the enableInDefaultView might be with wrong value

        $settings = SettingsStore::get('web_to_print', 'pimcore_web_to_print');
        if($settings) {
            $data = json_decode($settings->getData(), true);

            if(!isset($data['enableInDefaultView'])) {
                $data['enableInDefaultView'] = false;
                $data = json_encode($data);
                SettingsStore::set('web_to_print', $data,'string', 'pimcore_web_to_print');
            }
        }
    }
}
