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
        if ($schema->hasTable('glossary')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\GlossaryBundle\\PimcoreGlossaryBundle', true, 'bool', 'pimcore');
        }

        $this->warnIf(
            $schema->hasTable('glossary'),
            'Please make sure to enable the Pimcore\\Bundle\\GlossaryBundle\\PimcoreGlossaryBundle manually in config/bundles.php'
        );
    }
}
