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

pimcore.registerNS("pimcore.asset.folder");
pimcore.asset.folder = Class.create(pimcore.asset.asset, {

    initialize: function(id) {

        this.id = intval(id);
        this.setType("folder");
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "folder");

        this.properties = new pimcore.element.properties(this, "asset");
        this.dependencies = new pimcore.element.dependencies(this, "asset");
        this.notes = new pimcore.element.notes(this, "asset");
        this.listfolder = new pimcore.asset.listfolder(this);

        this.getData();
    },

    getTabPanel: function () {


        var items = [];


        this.store = new Ext.data.JsonStore({
            url: '/admin/asset/get-folder-content-preview',
            baseParams: {
                id: this.id
            },
            root: 'assets',
            fields: ['url', "filename", "type", "id", "idPath"],
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
        this.store.load();

        var tpl = new Ext.XTemplate(
            '<tpl for=".">',
            '<div class="thumb-wrap">',
            '<div class="thumb"><table cellspacing="0" cellpadding="0" border="0"><tr><td align="center" '
                + 'valign="middle" style="background: url({url}) center center no-repeat; ' +
                'background-size: contain;" id="{type}_{id}" data-idpath="{idPath}">'
                + '</td></tr></table></div>',
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
                store: this.store,
                autoScroll: true,
                tpl: tpl,
                emptyText: ' ',
                listeners: {
                    "click": function (view, index, node, event) {
                        var data = node.getAttribute("id").split("_");
                        pimcore.helpers.openAsset(data[1], data[0]);
                    },
                    "afterrender": function(el) {
                        el.on("contextmenu",  function(view, index, node, event) {
                            event.stopEvent();
                            this.showContextMenu(node, event);
                        }.bind(this), null, {preventDefault: true});

                    }.bind(this)
                }
            }),
            bbar: new Ext.PagingToolbar({
                pageSize: 10,
                store: this.store,
                displayInfo: true,
                displayMsg: '{0} - {1} / {2}',
                emptyMsg: t("no_assets_found")
            })
        });

        items.push(this.dataview);

        items.push(this.listfolder.getLayout());

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

    showContextMenu: function(node, event) {
        var data = node.getAttribute("id");
        var idPath = node.getAttribute("data-idpath");
        var splitted = data.split("_");
        var type = splitted[0];
        var id = splitted[1];

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('open'),
            iconCls: "pimcore_icon_open",
            handler: function (id, type) {
                pimcore.helpers.openAsset(id, type);
            }.bind(this, id, type)
        }));
        menu.add(new Ext.menu.Item({
            text: t('show_in_tree'),
            iconCls: "pimcore_icon_show_in_tree",
            handler: function (idPath) {
                try {
                    try {
                        Ext.getCmp("pimcore_panel_tree_assets").expand();
                        var tree = pimcore.globalmanager.get("layout_asset_tree");
                        pimcore.helpers.selectPathInTree(tree.tree, idPath);
                    } catch (e) {
                        console.log(e);
                    }

                } catch (e2) { console.log(e2); }
            }.bind(this, idPath)
        }));
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: function () {
                pimcore.helpers.deleteAsset(id, function() {
                    this.store.reload();
                    pimcore.globalmanager.get("layout_asset_tree").tree.getRootNode().reload();
                }.bind(this));
            }.bind(this, id)
        }));
        menu.showAt(event.getXY());
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
                text: t('delete_folder'),
                iconCls: "pimcore_icon_delete_medium",
                scale: "medium",
                handler: this.remove.bind(this)
            });
            if (this.isAllowed("delete") && !this.data.locked && this.data.id != 1) {
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

            var user = pimcore.globalmanager.get("user");
            if (user.admin) {
                buttons.push({
                    text: t("show_metainfo"),
                    scale: "medium",
                    iconCls: "pimcore_icon_info_large",
                    handler: this.showMetaInfo.bind(this)
                });
            }

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
        //pimcore.helpers.download('/admin/asset/download-as-zip/?id='+ this.id);

        Ext.Ajax.request({
            url: "/admin/asset/download-as-zip-jobs",
            params: {id: this.id},
            success: function(response) {
                var res = Ext.decode(response.responseText);

                this.downloadProgressBar = new Ext.ProgressBar({
                    text: t('initializing')
                });

                this.downloadProgressWin = new Ext.Window({
                    title: t("download_as_zip"),
                    layout:'fit',
                    width:500,
                    bodyStyle: "padding: 10px;",
                    closable:false,
                    plain: true,
                    modal: true,
                    items: [this.downloadProgressBar]
                });

                this.downloadProgressWin.show();


                var pj = new pimcore.tool.paralleljobs({
                    success: function (jobId) {
                        if(this.downloadProgressWin) {
                            this.downloadProgressWin.close();
                        }

                        this.downloadProgressBar = null;
                        this.downloadProgressWin = null;

                        pimcore.helpers.download('/admin/asset/download-as-zip/?jobId='+ jobId + "&id=" + this.id);
                    }.bind(this, res.jobId),
                    update: function (currentStep, steps, percent) {
                        if(this.downloadProgressBar) {
                            var status = currentStep / steps;
                            this.downloadProgressBar.updateProgress(status, percent + "%");
                        }
                    }.bind(this),
                    failure: function (message) {
                        this.downloadProgressWin.close();
                        pimcore.helpers.showNotification(t("error"), t("error"),
                            "error", t(message));
                    }.bind(this),
                    jobs: res.jobs
                });
            }.bind(this)
        });
    },

    showMetaInfo: function() {

        new pimcore.element.metainfo([
            {
                name: "id",
                value: this.data.id
            },
            {
                name: "path",
                value: this.data.path + this.data.filename
            }, {
                name: "type",
                value: this.data.type
            }, {
                name: "modificationdate",
                type: "date",
                value: this.data.modificationDate
            }, {
                name: "creationdate",
                type: "date",
                value: this.data.creationDate
            }, {
                name: "usermodification",
                type: "user",
                value: this.data.userModification
            }, {
                name: "userowner",
                type: "user",
                value: this.data.userOwner
            },
            {
                name: "deeplink",
                value: window.location.protocol + "//" + window.location.hostname + "/admin/login/deeplink?asset_" + this.data.id + "_" + this.data.type
            }
        ], "folder");
    }
});

