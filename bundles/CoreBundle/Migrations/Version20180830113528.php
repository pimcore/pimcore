<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\DataObject\Fieldcollection\Definition;

class Version20180830113528 extends AbstractPimcoreMigration
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
            $this->writeMessage("Updating field collection definition 'paymentState' - adding additional paymentState");

            try {
                $definition = Definition::getByKey('PaymentInfo');
            } catch (\Exception $e) {
            }

            if ($definition) {
                $fieldDefinition = $definition->getFieldDefinition('paymentState');

                if ($fieldDefinition) {
                    $options = $fieldDefinition->getOptions();
                    $options[] = [
                        'value' => 'abortedButResponseReceived',
                        'key' => 'Aborted but Response Received',
                    ];
                    $fieldDefinition->setOptions($options);

                    $this->writeMessage(" ... saving field collection definition 'paymentState'");
                    if (!$this->isDryRun()) {
                        $definition->save();
                    }
                }
            } else {
                $this->writeMessage(' ... nothing to do because field collection definition does not exist.');
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        if (PimcoreEcommerceFrameworkBundle::isEnabled()) {
            $this->writeMessage("Updating field collection definition 'paymentState' - removing additional paymentState");

            try {
                $definition = Definition::getByKey('PaymentInfo');
            } catch (\Exception $e) {
            }

            if ($definition) {
                $fieldDefinition = $definition->getFieldDefinition('paymentState');

                if ($fieldDefinition) {
                    $options = $fieldDefinition->getOptions();

                    $indexToDelete = null;
                    foreach ($options as $index => $option) {
                        if ($option['value'] == 'abortedButResponseReceived') {
                            $indexToDelete = $index;
                        }
                    }

                    if ($indexToDelete !== null) {
                        unset($options[$indexToDelete]);
                    }

                    $fieldDefinition->setOptions($options);

                    $this->writeMessage(" ... saving field collection definition 'paymentState'");
                    if (!$this->isDryRun()) {
                        $definition->save();
                    }
                }
            } else {
                $this->writeMessage(' ... nothing to do because field collection definition does not exist.');
            }
        }
    }
}
