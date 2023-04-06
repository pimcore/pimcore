<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Tool\SettingsStore;

final class Version20230406113010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Install newsletter bundle by default';
    }

    public function up(Schema $schema): void
    {
        if (!SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle', 'pimcore')) {
            SettingsStore::set('BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle', true, SettingsStore::TYPE_BOOLEAN, 'pimcore');
        }

        $this->warnIf(
            null !== SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle', 'pimcore'),
            'Please make sure to enable the BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle manually in config/bundles.php'
        );
    }

    public function down(Schema $schema): void
    {
        if (SettingsStore::get('BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle', 'pimcore')) {
            SettingsStore::delete('BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle', 'pimcore');
        }

        $this->write('Please deactivate the BUNDLE_INSTALLED__Pimcore\\Bundle\\NewsletterBundle\\PimcoreNewsletterBundle manually in config/bundles.php');
    }
}
