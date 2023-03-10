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
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;

final class Version20220809154926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate Custom Layouts from Legacy config to LocationAwareConfigRepository';
    }

    public function up(Schema $schema): void
    {
        $customLayouts = $this->loadLegacyCustomLayoutConfigs();

        foreach ($customLayouts as $customLayout) {
            $customLayout->save();
        }
    }

    public function down(Schema $schema): void
    {
        $customLayouts = $this->loadLegacyCustomLayoutConfigs();

        foreach ($customLayouts as $customLayout) {
            $customLayout->save();
        }
    }

    /**
     * @return CustomLayout[]
     */
    private function loadLegacyCustomLayoutConfigs(): array
    {
        $files = glob(PIMCORE_CLASS_DEFINITION_DIRECTORY . '/customlayouts/*.php');

        $layouts = [];
        foreach ($files as $file) {
            $layout = @include $file;
            if ($layout instanceof CustomLayout) {
                $layouts[$layout->getId()] = $layout;
            }
        }

        return $layouts;
    }
}
