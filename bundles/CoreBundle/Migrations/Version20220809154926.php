<?php

declare(strict_types=1);

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
