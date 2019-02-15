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

pimcore.registerNS("pimcore.asset.embedded_meta");
pimcore.asset.embedded_meta = Class.create({
    initialize: function(asset) {
        this.asset = asset;
    },

    getExifPanel: function () {
        if (!this.asset.exifPanel) {


            var data = this.asset.data.customSettings['meta-information'];

            if(!data){
                return null;
            }

            var newPanel = new Ext.grid.PropertyGrid({
                flex: 1,
                border: true,
                source: data || [],
                clicksToEdit: 1000
            });
            newPanel.plugins[0].disable();

            this.asset.exifPanel = new Ext.Panel({
                title: t("embedded_meta_info"),
                layout: {
                    type: 'hbox',
                    align: 'stretch'
                },
                iconCls: "pimcore_icon_exif",
                items: [newPanel]
            });
        }

        return this.asset.exifPanel;
    }
});