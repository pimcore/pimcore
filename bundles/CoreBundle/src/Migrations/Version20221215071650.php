<?php

declare(strict_types=1);

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
