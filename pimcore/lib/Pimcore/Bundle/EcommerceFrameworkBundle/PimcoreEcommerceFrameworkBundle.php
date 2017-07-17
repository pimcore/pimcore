<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle;

use Pimcore\Bundle\EcommerceFrameworkBundle\Legacy\LegacyClassMappingTool;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Installer;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class PimcoreEcommerceFrameworkBundle extends AbstractPimcoreBundle
{
    /**
     * @return array
     */
    public function getCssPaths()
    {
        return [
            '/bundles/pimcoreecommerceframework/css/backend.css',
            '/bundles/pimcoreecommerceframework/css/pricing.css'
        ];
    }

    /**
     * @return array
     */
    public function getJsPaths()
    {
        return [
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/data/indexFieldSelectionField.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/tags/indexFieldSelectionField.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/data/indexFieldSelectionCombo.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/tags/indexFieldSelectionCombo.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/data/indexFieldSelection.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/tags/indexFieldSelection.js',
            '/bundles/pimcoreecommerceframework/js/bundle.js',
            '/bundles/pimcoreecommerceframework/js/pricing/config/panel.js',
            '/bundles/pimcoreecommerceframework/js/pricing/config/item.js',
            '/bundles/pimcoreecommerceframework/js/pricing/config/objects.js',
            '/bundles/pimcoreecommerceframework/js/voucherservice/VoucherSeriesTab.js',
            '/admin/ecommerceframework/config/js-config'
        ];
    }

    public function boot()
    {
        if($this->getInstaller()->isInstalled()) {
            //load legacy class mapping only when ecommerce framework bundle is installed.
            LegacyClassMappingTool::loadMapping();
        }
    }

    /**
     * @return Installer
     */
    public function getInstaller()
    {
        return $this->container->get('pimcore.ecommerceframework.installer');
    }
}
