<?php

namespace Pimcore\Tests\Helper;

class Ecommerce extends AbstractDefinitionHelper
{
    public function initializeDefinitions()
    {
        $installSources = __DIR__ . '/../../../lib/Pimcore/Bundle/EcommerceFrameworkBundle/install';

        $cm = $this->getClassManager();

        $cm->setupFieldcollection(
            'TaxEntry',
            $installSources . '/fieldcollection_sources/fieldcollection_TaxEntry_export.json'
        );

        $cm->setupClass(
            'OnlineShopTaxClass',
            $installSources . '/class_source/class_OnlineShopTaxClass_export.json'
        );
    }
}
