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

pimcore.registerNS("pimcore.asset.embedded_meta_data");
pimcore.asset.embedded_meta_data = Class.create({
    initialize: function(asset) {
        this.asset = asset;
    },

    getPanel: function () {
        if (!this.panel) {


            var data = this.asset.data.customSettings['embeddedMetaData'];

            if(!data){
                return null;
            }

            var newPanel = new Ext.grid.PropertyGrid({
                source: data || [],
                clicksToEdit: 1000,
                viewConfig: {
                    listeners: {
                        refresh: function(dataview) {
                            dataview.panel.getColumns()[0].autoSize();
                        }
                    }
                }
            });
            newPanel.plugins[0].disable();

            this.panel = new Ext.Panel({
                title: t("embedded_meta_data"),
                layout: 'fit',
                iconCls: "pimcore_material_icon_embedded_metadata pimcore_material_icon",
                items: [newPanel]
            });
        }

        return this.panel;
    }
});