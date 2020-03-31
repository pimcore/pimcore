<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20191230103524 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if ($schema->hasTable('ecommerceframework_cartitem')) {
            if (!$schema->getTable('ecommerceframework_cartitem')->hasIndex('cartId_parentItemKey')) {
                $this->addSql('ALTER TABLE `ecommerceframework_cartitem` ADD INDEX `cartId_parentItemKey` (`cartId`,`parentItemKey`);');
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        if ($schema->hasTable('ecommerceframework_cartitem')) {
            if ($schema->getTable('ecommerceframework_cartitem')->hasIndex('cartId_parentItemKey')) {
                $this->addSql('ALTER TABLE `ecommerceframework_cartitem` DROP INDEX `cartId_parentItemKey`;');
            }
        }
    }
}
