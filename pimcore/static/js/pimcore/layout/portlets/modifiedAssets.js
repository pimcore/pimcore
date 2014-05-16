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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.layout.portlets.modifiedAssets");
pimcore.layout.portlets.modifiedAssets = Class.create(pimcore.layout.portlets.abstract, {

    getType: function () {
        return "pimcore.layout.portlets.modifiedAssets";
    },

    getName: function () {
        return t("modified_assets");
    },

    getIcon: function () {
        return "pimcore_icon_portlet_modified_assets";
    },

    getLayout: function (portletId) {

        var store = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/portal/portlet-modified-assets',
            root: 'assets',
            fields: ['id','path',"type",'date']
        });

        store.load();

        var grid = new Ext.grid.GridPanel({
            store: store,
            columns: [
                {header: t('path'), id: "path", sortable: false, dataIndex: 'path'},
                {header: t('date'), width: 130, sortable: false, renderer: function (d) {
                    var date = new Date(d * 1000);
                    return date.format("Y-m-d H:i:s");
                }, dataIndex: 'date'}
            ],
            stripeRows: true,
            autoExpandColumn: 'path'
        });

        grid.on("rowclick", function (grid, rowIndex, event) {
            var data = grid.getStore().getAt(rowIndex);

            pimcore.helpers.openAsset(data.data.id, data.data.type);
        });

        this.layout = new Ext.ux.Portlet(Object.extend(this.getDefaultConfig(), {
            title: this.getName(),
            iconCls: this.getIcon(),
            height: 275,
            layout: "fit",
            items: [grid]
        }));

        this.layout.portletId = portletId;
        return this.layout;
    }
});
