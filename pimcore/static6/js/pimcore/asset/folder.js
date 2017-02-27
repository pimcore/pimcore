/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.asset.folder");
pimcore.asset.folder = Class.create(pimcore.asset.asset, {

    initialize: function(id) {

        this.id = intval(id);
        this.setType("folder");
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "folder");

        var user = pimcore.globalmanager.get("user");

        this.properties = new pimcore.element.properties(this, "asset");
        this.dependencies = new pimcore.element.dependencies(this, "asset");

        if (user.isAllowed("notes_events")) {
            this.notes = new pimcore.element.notes(this, "asset");
        }

        this.tagAssignment = new pimcore.element.tag.assignment(this, "asset");
        this.listfolder = new pimcore.asset.listfolder(this);

        this.getData();
    },

    getTabPanel: function () {


        var items = [];
        var user = pimcore.globalmanager.get("user");

        var proxy = {
            type: 'ajax',
            url: '/admin/asset/get-folder-content-preview',
            reader: {
                type: 'json',
                rootProperty: 'assets'
            },
            extraParams: {
                id: this.id
            }
        };

        this.store = new Ext.data.Store({
            proxy: proxy,
            fields: ['url', "filename", "filenameDisplay", "type", "id", "idPath"],
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
            '<div class="thumb"><table cellspacing="0" cellpadding="0" border="0"><tr><td class="thumb-item" align="center" '
                + 'valign="middle" style="background: url({url}) center center no-repeat; ' +
                'background-size: contain;" id="{type}_{id}" data-idpath="{idPath}">'
                + '</td></tr></table></div>',
            '<span class="filename" title="{filename}">{filenameDisplay}</span></div>',
            '</tpl>',
            '<div class="x-clear"></div>'
        );

        var pageSize = pimcore.helpers.grid.getDefaultPageSize(-1);

        this.dataview = new Ext.Panel({
            layout:'fit',
            bodyCls: "asset_folder_preview",
            title: t("content"),
            iconCls: "pimcore_icon_asset",
            items: new Ext.DataView({
                store: this.store,
                autoScroll: true,
                tpl: tpl,
                itemSelector: 'td.thumb-item',
                emptyText: ' ',
                listeners: {
                    "itemclick": function (view, record, item, index, e, eOpts ) {
                        var data = item.getAttribute("id").split("_");
                        pimcore.helpers.openAsset(data[1], data[0]);
                    },
                    "afterrender": function(el) {
                        el.on("itemcontextmenu",
                            function(view, record, item, index, e, eOpts ) {
                                e.stopEvent();
                                this.showContextMenu(item, event, record);
                            }.bind(this),
                        null, {preventDefault: true});
                    }.bind(this)
                }
            }),
            bbar: pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: pageSize})
        });

        items.push(this.dataview);

        items.push(this.listfolder.getLayout());

        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }

        items.push(this.dependencies.getLayout());


        if (user.isAllowed("notes_events")) {
            items.push(this.notes.getLayout());
        }

        if (user.isAllowed("tags_assignment")) {
            items.push(this.tagAssignment.getLayout());
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

    showContextMenu: function(domEl, event, node) {
        var data = domEl.getAttribute("id");
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

        if (pimcore.elementservice.showLocateInTreeButton("asset")) {
            menu.add(new Ext.menu.Item({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_show_in_tree",
                handler: function () {
                    try {
                        try {
                            pimcore.treenodelocator.showInTree(node.id, "asset", this);
                        } catch (e) {
                            console.log(e);
                        }

                    } catch (e2) {
                        console.log(e2);
                    }
                }
            }));
        }

        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: function () {

                var options = {
                    "elementType" : "asset",
                    "id": id,
                    "success": function() {
                        this.store.reload();
                    }.bind(this)
                };

                pimcore.elementservice.deleteElement(options);
            }.bind(this, id)
        }));
        menu.showAt(event.pageX, event.pageY);
    },

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            var buttons = [];

            this.toolbarButtons = {};

            this.toolbarButtons.publish = new Ext.Button({
                text: t("save"),
                iconCls: "pimcore_icon_publish",
                scale: "medium",
                handler: this.save.bind(this)
            });

            if(this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }

            this.toolbarButtons.remove = new Ext.Button({
                tooltip: t('delete_folder'),
                iconCls: "pimcore_icon_delete",
                scale: "medium",
                handler: this.remove.bind(this)
            });

            this.toolbarButtons.rename = new Ext.Button({
                tooltip: t('rename'),
                iconCls: "pimcore_icon_key pimcore_icon_overlay_go",
                scale: "medium",
                handler: function () {
                    var options = {
                        elementType: "asset",
                        elementSubType: this.getType(),
                        id: this.id,
                        default: this.data.filename
                    }
                    pimcore.elementservice.editElementKey(options);
                }.bind(this)
            });

            buttons.push("-");

            if (this.isAllowed("delete") && !this.data.locked && this.data.id != 1) {
                buttons.push(this.toolbarButtons.remove);
            }
            if (this.isAllowed("rename") && !this.data.locked && this.data.id != 1) {
                buttons.push(this.toolbarButtons.rename);
            }
            
            buttons.push({
                tooltip: t("download_as_zip"),
                iconCls: "pimcore_icon_zip pimcore_icon_overlay_download",
                scale: "medium",
                handler: this.downloadZip.bind(this)
            });

            buttons.push({
                tooltip: t('reload'),
                iconCls: "pimcore_icon_reload",
                scale: "medium",
                handler: this.reload.bind(this)
            });

            if (pimcore.elementservice.showLocateInTreeButton("asset")) {
                buttons.push({
                    tooltip: t('show_in_tree'),
                    iconCls: "pimcore_icon_show_in_tree",
                    scale: "medium",
                    handler: this.selectInTree.bind(this)
                });
            }

            var user = pimcore.globalmanager.get("user");
            if (user.admin) {
                buttons.push({
                    tooltip: t("show_metainfo"),
                    iconCls: "pimcore_icon_info",
                    scale: "medium",
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
                cls: "main-toolbar",
                items: buttons,
                overflowHandler: 'scroller'
            });
        }

        return this.toolbar;
    },

    downloadZip: function () {
        //pimcore.helpers.download('/admin/asset/download-as-zip?id='+ this.id);

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

                        pimcore.helpers.download('/admin/asset/download-as-zip?jobId='+ jobId + "&id=" + this.id);
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

