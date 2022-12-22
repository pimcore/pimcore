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
        if ($schema->hasTable('glossary')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\GlossaryBundle\\GlossaryBundle', true, 'bool', 'pimcore');
        }

        $this->warnIf($schema->hasTable('glossary'), 'Please make sure to enable the bundle manually in config/bundles.php');
    }
}
