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

pimcore.registerNS("pimcore.asset.versions");
pimcore.asset.versions = Class.create({

    initialize: function(asset) {
        this.asset = asset;
    },

    getLayout: function () {

        if (this.layout == null) {

            var modelName = 'pimcore.model.assetversions';
            if (!Ext.ClassManager.get(modelName)) {
                Ext.define(modelName, {
                    extend: 'Ext.data.Model',
                    fields: ['id', 'date', 'scheduled', 'note', {
                        name: 'name', convert: function (v, rec) {
                            if (rec.data) {
                                if (rec.data.user) {
                                    if (rec.data.user.name) {
                                        return rec.data.user.name;
                                    }
                                }
                            }
                            return null;
                        }
                    }]
                });
            }

            this.store = new Ext.data.Store({
                model: modelName,
                sorters: [{
                    property: 'date',
                    direction: 'DESC'
                }],
                proxy: {
                    type: 'ajax',
                    url: "/admin/asset/get-versions",
                    extraParams: {
                        id: this.asset.id
                    },
                    // Reader is now on the proxy, as the message was explaining
                    reader: {
                        type: 'json',
                        rootProperty: 'versions'
                    }
                }

            });

            var grid = Ext.create('Ext.grid.Panel', {
                store: this.store,
                columns: [
                    {header: t("date"), width:130, sortable: true, dataIndex: 'date', renderer: function(d) {
                        var date = new Date(d * 1000);
                        return Ext.Date.format(date, "Y-m-d H:i:s");
                    }},
                    {header: t("user"), sortable: true, dataIndex: 'name'},
                    {header: t("scheduled"), width:130, sortable: true, dataIndex: 'scheduled', renderer: function(d) {
                        if (d != null){
                        	var date = new Date(d * 1000);
                            return Ext.Date.format(date, "Y-m-d H:i:s");
                    	}
                    }, editable: false}
                ],
                stripeRows: true,
                width:370,
                title: t('available_versions'),
                region: "west",
                viewConfig: {
                    getRowClass: function(record, rowIndex, rp, ds) {
                        if (record.data.date == this.asset.data.modificationDate) {
                            return "version_published";
                        }
                        return "";
                    }.bind(this)
                }
            });

            grid.on("rowclick", this.onRowClick.bind(this));
            grid.on("rowcontextmenu", this.onRowContextmenu.bind(this));
            grid.on("beforerender", function () {
                this.store.load();
            }.bind(this));

            grid.reference = this;

            var preview = new Ext.Panel({
                title: t("preview"),
                region: "center",
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="about:blank" frameborder="0" id="asset_version_iframe_'
                                                                    + this.asset.id + '"></iframe>'
            });

            this.layout = new Ext.Panel({
                title: t('versions'),
                bodyStyle:'padding:20px 5px 20px 5px;',
                border: false,
                layout: "border",
                iconCls: "pimcore_icon_tab_versions",
                items: [grid,preview]
            });

            preview.on("resize", this.onLayoutResize.bind(this));
        }

        return this.layout;
    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get("asset_version_iframe_" + this.asset.id).setStyle({
            width: width + "px",
            height: (height - 25) + "px"
        });
    },

    onRowClick: function(grid, record, tr, rowIndex, e, eOpts ) {
        var data = grid.getStore().getAt(rowIndex).data;

        var versionId = data.id;
        var path = "/admin/asset/show-version/id/" + versionId;
        Ext.get("asset_version_iframe_" + this.asset.id).dom.src = path;
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {

        //$(grid.getView().getRow(rowIndex)).animate(
        //            { backgroundColor: '#E0EAEE' }, 100).animate( { backgroundColor: '#fff' }, 400);

        var menu = new Ext.menu.Menu();

        if (this.asset.isAllowed("publish")) {
            menu.add(new Ext.menu.Item({
                text: t('publish'),
                iconCls: "pimcore_icon_publish",
                handler: this.publishVersion.bind(this, rowIndex, grid)
            }));
        }

        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.removeVersion.bind(this, rowIndex, grid)
        }));

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },

    removeVersion: function (index, grid) {

        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        Ext.Ajax.request({
            url: "/admin/asset/delete-version",
            params: {id: versionId}
        });

        grid.getStore().removeAt(index);
    },

    publishVersion: function (index, grid) {
        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        Ext.Ajax.request({
            url: "/admin/asset/publish-version",
            params: {id: versionId},
            success: this.asset.reload.bind(this.asset)
        });
    },

    reload: function () {
        this.store.reload();
    }
    
});