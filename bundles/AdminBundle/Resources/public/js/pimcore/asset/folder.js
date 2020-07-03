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

pimcore.registerNS("pimcore.asset.folder");
pimcore.asset.folder = Class.create(pimcore.asset.asset, {

    initialize: function(id, options) {

        this.options = options;
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
        this.listfolder = new pimcore.asset.listfolder(this, "folder");
        this.workflows = new pimcore.element.workflows(this, "asset");

        this.getData();
    },

    getTabPanel: function () {


        var items = [];
        var user = pimcore.globalmanager.get("user");

        var proxy = {
            type: 'ajax',
            url: Routing.generate('pimcore_admin_asset_getfoldercontentpreview'),
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
            title: t("preview"),
            iconCls: "pimcore_material_icon_devices pimcore_material_icon",
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
                                this.showContextMenu(item, e, record);
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

        if (user.isAllowed("workflow_details") && this.data.workflowManagement && this.data.workflowManagement.hasWorkflowManagement === true) {
            items.push(this.workflows.getLayout());
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

        if (this.isAllowed("delete")) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function () {

                    var options = {
                        "elementType": "asset",
                        "id": id,
                        "success": function () {
                            this.store.reload();
                        }.bind(this)
                    };

                    pimcore.elementservice.deleteElement(options);
                }.bind(this, id)
            }));
        }
        menu.showAt(event.pageX, event.pageY);
    },

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            var buttons = [];

            this.toolbarButtons = {};

            this.toolbarButtons.publish = new Ext.Button({
                text: t("save"),
                iconCls: "pimcore_icon_save_white",
                cls: "pimcore_save_button",
                scale: "medium",
                handler: this.save.bind(this)
            });

            if(this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }

            this.toolbarButtons.remove = new Ext.Button({
                tooltip: t('delete_folder'),
                iconCls: "pimcore_material_icon_delete pimcore_material_icon",
                scale: "medium",
                handler: this.remove.bind(this)
            });

            this.toolbarButtons.rename = new Ext.Button({
                tooltip: t('rename'),
                iconCls: "pimcore_material_icon_rename pimcore_material_icon",
                scale: "medium",
                handler: this.rename.bind(this)
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
                iconCls: "pimcore_material_icon_download_zip pimcore_material_icon",
                scale: "medium",
                handler: function () {
                    pimcore.elementservice.downloadAssetFolderAsZip(this.id)
                }.bind(this)
            });

            buttons.push({
                tooltip: t('reload'),
                iconCls: "pimcore_material_icon_reload pimcore_material_icon",
                scale: "medium",
                handler: this.reload.bind(this)
            });

            if (pimcore.elementservice.showLocateInTreeButton("asset")) {
                buttons.push({
                    tooltip: t('show_in_tree'),
                    iconCls: "pimcore_material_icon_locate pimcore_material_icon",
                    scale: "medium",
                    handler: this.selectInTree.bind(this)
                });
            }

            var user = pimcore.globalmanager.get("user");
            if (user.admin) {
                buttons.push({
                    xtype: "splitbutton",
                    tooltip: t("show_metainfo"),
                    iconCls: "pimcore_material_icon_info pimcore_material_icon",
                    scale: "medium",
                    handler: this.showMetaInfo.bind(this),
                    menu: this.getMetaInfoMenuItems()
                });
            }

            buttons.push("-");
            buttons.push({
                xtype: 'tbtext',
                text: t("id") + " " + this.data.id,
                scale: "medium"
            });

            this.toolbar = new Ext.Toolbar({
                id: "asset_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "pimcore_main_toolbar",
                items: buttons,
                overflowHandler: 'scroller'
            });
        }

        return this.toolbar;
    },

    getMetaInfo: function() {
        return {
            id: this.data.id,
            path: this.data.path + this.data.filename,
            type: this.data.type,
            modificationdate: this.data.modificationDate,
            creationdate: this.data.creationDate,
            usermodification: this.data.userModification,
            userowner: this.data.userOwner,
            deeplink: pimcore.helpers.getDeeplink("asset", this.data.id, this.data.type)
        };
    },

    showMetaInfo: function() {
        var metainfo = this.getMetaInfo();

        new pimcore.element.metainfo([
            {
                name: "id",
                value: metainfo.id
            },
            {
                name: "path",
                value: metainfo.path
            }, {
                name: "type",
                value: metainfo.type
            }, {
                name: "modificationdate",
                type: "date",
                value: metainfo.modificationdate
            }, {
                name: "creationdate",
                type: "date",
                value: metainfo.creationdate
            }, {
                name: "usermodification",
                type: "user",
                value: metainfo.usermodification
            }, {
                name: "userowner",
                type: "user",
                value: metainfo.userowner
            },
            {
                name: "deeplink",
                value: metainfo.deeplink
            }
        ], "folder");
    },

    rename: function () {
        if (this.isAllowed("rename") && !this.data.locked && this.data.id != 1) {
            var options = {
                elementType: "asset",
                elementSubType: this.getType(),
                id: this.id,
                default: this.data.filename
            }
            pimcore.elementservice.editElementKey(options);
        }
    }
});

