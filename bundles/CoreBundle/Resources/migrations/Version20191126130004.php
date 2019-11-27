<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191126130004 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if (PimcoreEcommerceFrameworkBundle::isEnabled()) {
            $this->addSql('CREATE INDEX IF NOT EXISTS ecommerceframework_cart_userid_index on ecommerceframework_cart (userid);');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        if (PimcoreEcommerceFrameworkBundle::isEnabled()) {
            $this->addSql('DROP INDEX IF EXISTS ecommerceframework_cart_userid_index on ecommerceframework_cart;');
        }
    }
}
