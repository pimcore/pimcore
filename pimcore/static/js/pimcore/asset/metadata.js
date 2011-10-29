/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.asset.metadata");
pimcore.asset.metadata = Class.create({

    initialize: function(asset) {
        this.asset = asset;
    },

    getLayout: function () {

        if (this.layout == null) {

            this.layout = new Ext.Panel({
                title: t('metadata'),
                bodyStyle:'padding:10px',
                border: false,
                iconCls: "pimcore_icon_metadata"
            });
        }

        return this.layout;
    }
});