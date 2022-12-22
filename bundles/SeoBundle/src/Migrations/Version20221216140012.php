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

namespace Pimcore\Bundle\SeoBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Tool\SettingsStore;

/**
 * Seo will be enabled by default
 */
final class Version20221216140012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if(!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\SeoBundle\\SeoBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\SeoBundle\\SeoBundle', true, 'bool', 'pimcore');
        }

        $this->warnIf(null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\SeoBundle\\SeoBundle', 'pimcore'), 'Please make sure to enable the bundle manually in config/bundles.php');
    }
}
