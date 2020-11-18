<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201008082752 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('tracking_events')) {
            $this->addSql('RENAME TABLE tracking_events TO PLEASE_DELETE__tracking_events;');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE PLEASE_DELETE__tracking_events TO tracking_events;');
    }
}
