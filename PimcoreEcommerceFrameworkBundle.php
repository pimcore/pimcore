<?php

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle;

use Pimcore\API\Bundle\AbstractPimcoreBundle;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Legacy\LegacyClassMappingTool;

class PimcoreEcommerceFrameworkBundle extends AbstractPimcoreBundle
{

    public function getCssPaths()
    {
        return [
            '/bundles/pimcoreecommerceframework/css/backend.css',
            '/bundles/pimcoreecommerceframework/css/pricing.css'
        ];
    }

    public function getJsPaths()
    {
        return [
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/data/indexFieldSelectionField.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/tags/indexFieldSelectionField.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/data/indexFieldSelectionCombo.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/tags/indexFieldSelectionCombo.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/data/indexFieldSelection.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/tags/indexFieldSelection.js',
            '/bundles/pimcoreecommerceframework/js/plugin.js',
            '/bundles/pimcoreecommerceframework/js/pricing/config/panel.js',
            '/bundles/pimcoreecommerceframework/js/pricing/config/item.js',
            '/bundles/pimcoreecommerceframework/js/pricing/config/objects.js',
            '/bundles/pimcoreecommerceframework/js/voucherservice/VoucherSeriesTab.js',
            '/plugin/EcommerceFramework/config/js-config'
        ];
    }

    public function boot()
    {
        parent::boot();

        //load legacy class mapping
        LegacyClassMappingTool::loadMapping();

    }
}
