<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220119082511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Use foreign key for delete cascade on gridconfig_favourites & gridconfig_shares';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `gridconfig_favourites`
        CHANGE `gridConfigId` `gridConfigId` int(11) NOT NULL,
            ADD CONSTRAINT `fk_gridconfig_favourites_gridconfigs`
            FOREIGN KEY (`gridConfigId`)
            REFERENCES `gridconfigs` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `gridconfig_shares`
            ADD CONSTRAINT `fk_gridconfig_shares_gridconfigs`
            FOREIGN KEY (`gridConfigId`)
            REFERENCES `gridconfigs` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `gridconfig_favourites`
            DROP FOREIGN KEY `fk_gridconfig_favourites_gridconfigs`,
            CHANGE `gridConfigId` `gridConfigId` int(11) NULL;');

        $this->addSql('ALTER TABLE `gridconfig_shares`
            DROP FOREIGN KEY `fk_gridconfig_shares_gridconfigs`;');
    }
}
