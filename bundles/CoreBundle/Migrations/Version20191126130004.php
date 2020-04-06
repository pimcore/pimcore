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
            $table = $schema->getTable('ecommerceframework_cart');
            if (!$table->hasIndex('ecommerceframework_cart_userid_index')) {
                $table->addIndex(['userid'], 'ecommerceframework_cart_userid_index');
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        if (PimcoreEcommerceFrameworkBundle::isEnabled()) {
            $table = $schema->getTable('ecommerceframework_cart');
            if ($table->hasIndex('ecommerceframework_cart_userid_index')) {
                $table->dropIndex('ecommerceframework_cart_userid_index');
            }
        }
    }
}
