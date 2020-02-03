<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\DefinitionModifier;
use Pimcore\Model\DataObject\Fieldcollection;

class Version20200113120101 extends AbstractPimcoreMigration
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
            $this->writeMessage("Updating OrderPriceModifications definition - adding additional fields 'pricingRuleId'");

            try {
                $definition = Fieldcollection\Definition::getByKey('OrderPriceModifications');
            } catch (\Exception $e) {
            }

            if ($definition) {
                $fields = [];

                if (!$definition->getFieldDefinition('pricingRuleId')) {
                    $fieldDefinition = new ClassDefinition\Data\Numeric();
                    $fieldDefinition->setName('pricingRuleId');
                    $fieldDefinition->setTitle('Applied pricing rule ID');
                    $fieldDefinition->setNoteditable(true);
                    $fieldDefinition->setVisibleGridView(false);
                    $fieldDefinition->setVisibleSearch(false);
                    $fieldDefinition->setInteger(true);
                    $fields[] = $fieldDefinition;
                }

                $layout = $definition->getLayoutDefinitions();

                $modifier = new DefinitionModifier();
                $modifier->appendFields($layout, 'netAmount', $fields);

                $definition->setLayoutDefinitions($layout);
                $definition->save();
            } else {
                $this->writeMessage(' ... nothing to do because OrderPriceModifications Fieldcollection does not exist.');
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->writeMessage("No automatic downgrading due to possible data loss. Please manually remove field 'pricingRuleId' from OrderPriceModifications Fieldcollection definition.");
    }
}
