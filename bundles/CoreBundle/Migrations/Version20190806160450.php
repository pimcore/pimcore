<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\DefinitionModifier;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190806160450 extends AbstractPimcoreMigration
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
            $this->writeMessage("Updating OnlineShopOrder definition - adding additional fields 'cartHash' and 'successorOrder'");

            try {
                $definition = ClassDefinition::getByName('OnlineShopOrder');
            } catch (\Exception $e) {
            }

            if ($definition) {
                $fields = [];

                if (!$definition->getFieldDefinition('cartHash')) {
                    $fieldDefinition = new ClassDefinition\Data\Numeric();
                    $fieldDefinition->setName('cartHash');
                    $fieldDefinition->setTitle('Cart Hash');
                    $fieldDefinition->setNoteditable(true);
                    $fieldDefinition->setVisibleGridView(false);
                    $fieldDefinition->setVisibleSearch(false);
                    $fieldDefinition->setInteger(true);
                    $fields[] = $fieldDefinition;
                }

                if (!$definition->getFieldDefinition('successorOrder')) {
                    $fieldDefinition = new ClassDefinition\Data\ManyToOneRelation();
                    $fieldDefinition->setName('successorOrder');
                    $fieldDefinition->setTitle('Successor Order');
                    $fieldDefinition->setNoteditable(true);
                    $fieldDefinition->setVisibleGridView(false);
                    $fieldDefinition->setVisibleSearch(false);
                    $fieldDefinition->setObjectsAllowed(true);
                    $fieldDefinition->setClasses(['OnlineShopOrder']);
                    $fields[] = $fieldDefinition;
                }

                $layout = $definition->getLayoutDefinitions();

                $modifier = new DefinitionModifier();
                $modifier->appendFields($layout, 'cartId', $fields);

                $definition->setLayoutDefinitions($layout);
                $definition->save();
            } else {
                $this->writeMessage(' ... nothing to do because OnlineShopOrder does not exist.');
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->writeMessage("No automatic downgrading due to possible data loss. Please manually remove fields 'cartHash' and 'successorOrder' from OnlineShopOrder class definition.");
    }
}
