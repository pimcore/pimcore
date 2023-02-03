<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230203160742 extends AbstractMigration
{
    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Modify `itemId` column type in `uuids` db table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `uuids` MODIFY COLUMN `itemId` VARCHAR(50) NOT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `uuids` MODIFY COLUMN `itemId` int(11) unsigned NOT NULL;');
    }
}
