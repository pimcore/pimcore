<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Config;
use Pimcore\Model\Tool\SettingsStore;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210323110055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $file = Config::locateConfigFile('robots.php');
        $config = Config::getConfigInstance($file);
        $config = $config->toArray();

        foreach ($config as $siteId => $robotsContent) {
            SettingsStore::set('robots.txt-' . $siteId, $robotsContent, 'string', 'robots.txt');
        }
    }

    public function down(Schema $schema): void
    {
        $robotsSettingsIds = SettingsStore::getIdsByScope('robots.txt');
        foreach ($robotsSettingsIds as $id) {
            SettingsStore::delete($id, 'robots.txt');
        }
    }
}
