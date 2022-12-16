<?php

declare(strict_types=1);

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
    }

    public function down(Schema $schema): void
    {
        // nothing to do here
    }
}
