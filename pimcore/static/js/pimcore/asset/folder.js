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

    type: "document",

    initialize: function(id) {

        this.setType("folder");

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "folder");

        this.addLoadingPanel();
        this.id = intval(id);

        this.properties = new pimcore.element.properties(this, "asset");
        this.permissions = new pimcore.asset.permissions(this);
        this.dependencies = new pimcore.element.dependencies(this, "asset");

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
        if (this.isAllowed("permissions")) {
            items.push(this.permissions.getLayout());
        }
        items.push(this.dependencies.getLayout());

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

            this.toolbarButtons = {};

            this.toolbarButtons.publish = new Ext.Button({
                text: t("save"),
                iconCls: "pimcore_icon_publish_medium",
                scale: "medium",
                handler: this.save.bind(this)
            });
            
            this.toolbarButtons.download = new Ext.Button({
                text: t("download_as_zip"),
                iconCls: "pimcore_icon_download_medium",
                scale: "medium",
                handler: this.downloadZip.bind(this)
            });

            this.toolbar = new Ext.Toolbar({
                id: "asset_toolbar_" + this.id,
                region: "north",
                border: false,
                height: 26,
                cls: "document_toolbar",
                items: [this.toolbarButtons.publish, "-", this.toolbarButtons.download, "-",{
                    text: this.data.id,
                    disabled: true
                }]
            });
        }

        return this.toolbar;
    },
    
    downloadZip: function () {
        location.href = '/admin/asset/download-as-zip/?id='+ this.id;
    }
});

