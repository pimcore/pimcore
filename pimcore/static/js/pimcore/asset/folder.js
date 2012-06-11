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

pimcore.registerNS("pimcore.asset.folder");
pimcore.asset.folder = Class.create(pimcore.asset.asset, {

    initialize: function(id) {

        this.setType("folder");

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "folder");

        this.addLoadingPanel();
        this.id = intval(id);

        this.properties = new pimcore.element.properties(this, "asset");
        this.dependencies = new pimcore.element.dependencies(this, "asset");
        this.notes = new pimcore.element.notes(this, "asset");

        this.getData();
    },

    getTabPanel: function () {


        var items = [];


        var store = new Ext.data.JsonStore({
            url: '/admin/asset/get-folder-content-preview',
            baseParams: {
                id: this.id
            },
            root: 'assets',
            fields: ['url', "filename", "type", "id"],
            listeners: {
                "load": function () {
                    try {
                        this.dataview.reload();
                    }
                    catch (e) {
                    }
                }.bind(this),
                "datachanged": function () {
                    try {
                        this.dataview.reload();
                    }
                    catch (e) {
                    }
                }.bind(this)
            }
        });
        store.load();

        var tpl = new Ext.XTemplate(
                '<tpl for=".">',
                '<div class="thumb-wrap">',
                '<div class="thumb"><table cellspacing="0" cellpadding="0" border="0"><tr><td align="center" valign="middle"><img src="{url}"  id="{type}_{id}" align="center" title="{name}"></td></tr></table></div>',
                '<span class="filename">{filename}</span></div>',
                '</tpl>',
                '<div class="x-clear"></div>'
                );

        this.dataview = new Ext.Panel({
            layout:'fit',
            bodyCssClass: "asset_folder_preview",
            title: t("content"),
            iconCls: "pimcore_icon_asset_folder_preview",
            items: new Ext.DataView({
                store: store,
                autoScroll: true,
                tpl: tpl,
                emptyText: ' ',
                listeners: {
                    "click": function (view, index, node, event) {

                        var data = node.getAttribute("id").split("_");
                        pimcore.helpers.openAsset(data[1], data[0]);
                    }
                }
            }),
            bbar: new Ext.PagingToolbar({
                pageSize: 10,
                store: store,
                displayInfo: true,
                displayMsg: '{0} - {1} / {2}',
                emptyMsg: "No topics to display"
            })
        });

        items.push(this.dataview);


        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }

        items.push(this.dependencies.getLayout());

        if (this.isAllowed("settings")) {
            items.push(this.notes.getLayout());
        }

        this.tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            border: false,
            items: items,
            activeTab: 0
        });

        return this.tabbar;
    },

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            var buttons = [];

            this.toolbarButtons = {};

            this.toolbarButtons.publish = new Ext.Button({
                text: t("save"),
                iconCls: "pimcore_icon_publish_medium",
                scale: "medium",
                handler: this.save.bind(this)
            });

            if(this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }

            this.toolbarButtons.remove = new Ext.Button({
                 text: t('delete'),
                 iconCls: "pimcore_icon_delete_medium",
                 scale: "medium",
                 handler: this.remove.bind(this)
            });
            if (this.isAllowed("delete") && !this.data.locked) {
                buttons.push(this.toolbarButtons.remove);
            }
            
            this.toolbarButtons.download = new Ext.Button({
                text: t("download_as_zip"),
                iconCls: "pimcore_icon_download_zip_medium",
                scale: "medium",
                handler: this.downloadZip.bind(this)
            });
            buttons.push(this.toolbarButtons.download);

            buttons.push("-");

            this.toolbarButtons.reload = new Ext.Button({
                text: t('reload'),
                iconCls: "pimcore_icon_reload_medium",
                scale: "medium",
                handler: this.reload.bind(this)
            });
            buttons.push(this.toolbarButtons.reload);

            buttons.push({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_download_showintree",
                scale: "medium",
                handler: this.selectInTree.bind(this)
            });

            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: this.data.id,
                scale: "medium"
            });

            this.toolbar = new Ext.Toolbar({
                id: "asset_toolbar_" + this.id,
                region: "north",
                border: false,
                height: 26,
                cls: "document_toolbar",
                items: buttons
            });
        }

        return this.toolbar;
    },
    
    downloadZip: function () {
        pimcore.helpers.download('/admin/asset/download-as-zip/?id='+ this.id);
    }
});

