<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Modifies foreign key constraints for specified tables, changing their names from ending in '_o_id' to '_id' and adding ON DELETE CASCADE.
 * The down method reverses the naming back to '_o_id' while keeping ON DELETE CASCADE.
 */
final class Version20240222143211 extends AbstractMigration
{
    private array $alterations = [
        'object_brick_query_Bodywork_CAR' => 'fk_object_brick_query_Bodywork_CAR__id',
        'object_brick_query_Dimensions_CAR' => 'fk_object_brick_query_Dimensions_CAR__id',
        'object_brick_query_Engine_CAR' => 'fk_object_brick_query_Engine_CAR__id',
        'object_brick_query_SaleInformation_AP' => 'fk_object_brick_query_SaleInformation_AP__id',
        'object_brick_query_SaleInformation_CAR' => 'fk_object_brick_query_SaleInformation_CAR__id',
        'object_brick_query_Transmission_CAR' => 'fk_object_brick_query_Transmission_CAR__id',
        'object_brick_store_Bodywork_CAR' => 'fk_object_brick_store_Bodywork_CAR__id',
        'object_brick_store_Dimensions_CAR' => 'fk_object_brick_store_Dimensions_CAR__id',
        'object_brick_store_Engine_CAR' => 'fk_object_brick_store_Engine_CAR__id',
        'object_brick_store_SaleInformation_AP' => 'fk_object_brick_store_SaleInformation_AP__id',
        'object_brick_store_SaleInformation_CAR' => 'fk_object_brick_store_SaleInformation_CAR__id',
        'object_brick_store_Transmission_CAR' => 'fk_object_brick_store_SaleInformation_CAR__id',
        'object_classificationstore_data_AP' => 'fk_object_classificationstore_data_AP__id',
        'object_classificationstore_groups_AP' => 'fk_object_classificationstore_groups_AP__id',
        'object_collection_FilterCategoryMultiselect_EF_FD' => 'fk_object_collection_FilterCategoryMultiselect_EF_FD__id',
        'object_collection_FilterCategory_EF_FD' => 'fk_object_collection_FilterCategory_EF_FD__id',
        'object_collection_FilterInputfield_EF_FD' => 'fk_object_collection_FilterInputfield_EF_FD__id',
        'object_collection_FilterMultiRelation_EF_FD' => 'fk_object_collection_FilterMultiRelation_EF_FD__id',
        'object_collection_FilterMultiSelect_EF_FD' => 'fk_object_collection_FilterMultiSelect_EF_FD__id',
        'object_collection_FilterNumberRangeSelection_EF_FD' => 'fk_object_collection_FilterNumberRangeSelection_EF_FD__id',
        'object_collection_FilterNumberRange_EF_FD' => 'fk_object_collection_FilterNumberRange_EF_FD__id',
        'object_collection_FilterRelation_EF_FD' => 'fk_object_collection_FilterRelation_EF_FD__id',
        'object_collection_FilterSelectFromMultiSelect_EF_FD' => 'fk_object_collection_FilterSelectFromMultiSelect_EF_FD__id',
        'object_collection_FilterSelect_EF_FD' => 'fk_object_collection_FilterSelect_EF_FD__id',
        'object_collection_NewsCars_NE' => 'fk_object_collection_NewsCars_NE__id',
        'object_collection_NewsLinks_NE' => 'fk_object_collection_NewsLinks_NE__id',
        'object_collection_NewsText_NE' => 'fk_object_collection_NewsText_NE__id',
        'object_collection_OrderByFields_EF_FD' => 'fk_object_collection_OrderByFields_EF_FD__id',
        'object_collection_OrderPriceModifications_EF_OSO' => 'fk_object_collection_OrderPriceModifications_EF_OSO__id',
        'object_collection_PaymentInfo_EF_OSO' => 'fk_object_collection_PaymentInfo_EF_OSO__id',
        'object_collection_PricingRule_EF_OSOI' => 'fk_object_collection_PricingRule_EF_OSOI__id',
        'object_collection_SimilarityField_EF_FD' => 'fk_object_collection_SimilarityField_EF_FD__id',
        'object_collection_TaxEntry_EF_OSTC' => 'fk_object_collection_TaxEntry_EF_OSTC__id',
        'object_collection_VoucherTokenTypePattern_EF_OSVS' => 'fk_object_collection_VoucherTokenTypePattern_EF_OSVS__id',
        'object_collection_VoucherTokenTypeSingle_EF_OSVS' => 'fk_object_collection_VoucherTokenTypeSingle_EF_OSVS__id',
        'object_metadata_CU' => 'fk_object_metadata_CU__id',
        'object_metadata_portaluser' => 'fk_object_metadata_portaluser__id',
        'object_metadata_portalusergroup' => 'fk_object_metadata_portalusergroup__id',

    ];

    public function getDescription(): string
    {
        return 'Rename o_id foreign keys to id.';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->alterations as $tableName => $newForeignKey) {
            $metaDataTable = $schema->getTable($tableName);
            $originalForeignKey = preg_replace('/_id$/', '_o_id', $newForeignKey);

            if ($metaDataTable->hasForeignKey($originalForeignKey)) {
                $this->addSql("ALTER TABLE {$tableName} DROP FOREIGN KEY {$originalForeignKey}");
                $this->addSql("ALTER TABLE {$tableName} ADD CONSTRAINT {$newForeignKey} FOREIGN KEY (id) REFERENCES objects(id) ON DELETE CASCADE");
            }
        }

    }

    public function down(Schema $schema): void
    {
        foreach ($this->alterations as $tableName => $newForeignKey) {
            $metaDataTable = $schema->getTable($tableName);
            $originalForeignKey = preg_replace('/_id$/', '_o_id', $newForeignKey);

            if ($metaDataTable->hasForeignKey($newForeignKey)) {
                $this->addSql("ALTER TABLE {$tableName} DROP FOREIGN KEY {$newForeignKey}");
                $this->addSql("ALTER TABLE {$tableName} ADD CONSTRAINT {$originalForeignKey} FOREIGN KEY (id) REFERENCES objects(id) ON DELETE CASCADE");
            }
        }
    }
}
