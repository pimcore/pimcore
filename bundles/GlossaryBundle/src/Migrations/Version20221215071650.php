<?php

declare(strict_types=1);

namespace Pimcore\Bundle\GlossaryBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Db;
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
        $tableExists = Db::get()->fetchOne('SHOW TABLES LIKE "glossary"');

        $installed = !empty($tableExists);

        if ($installed) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\GlossaryBundle\\GlossaryBundle', $installed, 'bool', 'pimcore');
        }
    }

    public function down(Schema $schema): void
    {
        // nothing to do here
    }
}
