<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\DataObject\ClassDefinition;

class Version20180830122128 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if (PimcoreEcommerceFrameworkBundle::isEnabled()) {
            $this->writeMessage("Updating class definition 'OnlineShopOrder' - adding index to cartId");

            $classDefinition = ClassDefinition::getByName('OnlineShopOrder');

            if ($classDefinition) {
                $fieldDefinition = $classDefinition->getFieldDefinition('cartId');

                if ($fieldDefinition) {
                    $fieldDefinition->setIndex(true);

                    $this->writeMessage(" ... saving class definition 'OnlineShopOrder'");
                    if (!$this->isDryRun()) {
                        $classDefinition->save();
                    }
                }
            } else {
                $this->writeMessage(' ... nothing to do because class definition does not exist.');
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        if (PimcoreEcommerceFrameworkBundle::isEnabled()) {
            $this->writeMessage("Updating class definition 'OnlineShopOrder' - removing index to cartId");

            $classDefinition = ClassDefinition::getByName('OnlineShopOrder');

            if ($classDefinition) {
                $fieldDefinition = $classDefinition->getFieldDefinition('cartId');

                if ($fieldDefinition) {
                    $fieldDefinition->setIndex(false);

                    $this->writeMessage(" ... saving class definition 'OnlineShopOrder'");
                    if (!$this->isDryRun()) {
                        $classDefinition->save();
                    }
                }
            } else {
                $this->writeMessage(' ... nothing to do because class definition does not exist.');
            }
        }
    }
}
