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

pimcore.registerNS("pimcore.document.versions");
pimcore.document.versions = Class.create({

    initialize: function(document) {
        this.document = document;
    },

    getLayout: function () {

        if (this.layout == null) {

            this.store = new Ext.data.JsonStore({
                autoDestroy: true,
                url: "/admin/document/get-versions",
                baseParams: {
                    id: this.document.id
                },
                root: 'versions',
                sortInfo: {
                    field: 'date',
                    direction: 'DESC'
                },
                fields: ['id', 'date', 'note', {name:'name', convert: function (v, rec) {
                    if (rec.user) {
                        if (rec.user.name) {
                            return rec.user.name;
                        }
                    }
                    return null;
                }},"public","show", "scheduled", {name:'publicurl', convert: function (v, rec) {
                    return this.document.data.path + this.document.data.key + "?v=" + rec.id;
                }.bind(this)}]
            });
            this.store.on("update", this.dataUpdate.bind(this));

            var checkPublic = new Ext.grid.CheckColumn({
                header: t("public"),
                dataIndex: "public",
                width: 50
            });

            var checkShow = new Ext.grid.CheckColumn({
                header: t("show"),
                dataIndex: "show",
                width: 50
            });

            this.grid = new Ext.grid.EditorGridPanel({
                store: this.store,
                columns: [
                    checkShow,
                    {header: "ID", sortable: true, dataIndex: 'id', editable: false, width: 40},
                    {header: t("date"), width:130, sortable: true, dataIndex: 'date', renderer: function(d) {
                        var date = new Date(d * 1000);
                        return date.format("Y-m-d H:i:s");
                    }, editable: false},
                    {header: t("user"), sortable: true, dataIndex: 'name', editable: false},
                    {header: t("scheduled"), width:130, sortable: true, dataIndex: 'scheduled', renderer: function(d) {
                        if (d != null){
                            var date = new Date(d * 1000);
                            return date.format("Y-m-d H:i:s");
                        }
                    }, editable: false},
                    {header: t("note"), sortable: true, dataIndex: 'note', editor: new Ext.form.TextField()},
                    checkPublic,
                    {header: t("public_url"), width:300, sortable: false, dataIndex: 'publicurl', editable: false}
                ],
                columnLines: true,
                trackMouseOver: true,
                stripeRows: true,
                plugins: [checkPublic,checkShow],
                width:600,
                title: t('available_versions'),
                region: "west",
                viewConfig: {
                    getRowClass: function(record, rowIndex, rp, ds) {
                        if (record.data.date == this.document.data.modificationDate) {
                            return "version_published";
                        }
                        return "";
                    }.bind(this)
                }
            });

            //this.grid.on("rowclick", this.onRowClick.bind(this));
            this.grid.on("rowcontextmenu", this.onRowContextmenu.bind(this));
            this.grid.on("beforerender", function () {
                this.store.load();
            }.bind(this));
            this.grid.reference = this;

            var preview = new Ext.Panel({
                title: t("preview"),
                region: "center",
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="about:blank" frameborder="0" id="document_version_iframe_'
                    + this.document.id + '"></iframe>'
            });

            this.layout = new Ext.Panel({
                title: t('versions'),
                bodyStyle:'padding:20px 5px 20px 5px;',
                border: false,
                layout: "border",
                iconCls: "pimcore_icon_tab_versions",
                items: [this.grid,preview]
            });

            preview.on("resize", this.onLayoutResize.bind(this));
        }

        return this.layout;
    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get("document_version_iframe_" + this.document.id).setStyle({
            width: width + "px",
            height: (height - 25) + "px"
        });
    },

    checkForPreview: function (store) {

        var displayRecords = store.query("show", true);

        if (displayRecords.items) {
            var length = displayRecords.items.length;
            if (length > 0) {
                if (length == 1) {
                    this.showVersionPreview(displayRecords.get(0).data.id);
                }
                else if (length == 2) {
                    this.compareVersions(displayRecords.get(0).data.id, displayRecords.get(1).data.id);
                }
                else {
                    Ext.MessageBox.alert(t("error"), t("maximum_2_versions"));
                }
            }
        }
    },

    compareVersions: function (id1, id2) {
        var path = "/admin/document/diff-versions/from/" + id1 + "/to/" + id2;
        Ext.get("document_version_iframe_" + this.document.id).dom.src = path;
    },

    showVersionPreview: function (id) {
        var path = this.document.data.path + this.document.data.key + "?pimcore_version=" + id;
        Ext.get("document_version_iframe_" + this.document.id).dom.src = path;
    },

    onRowContextmenu: function (grid, rowIndex, event) {

        $(grid.getView().getRow(rowIndex)).animate( { backgroundColor: '#E0EAEE' }, 100)
            .animate( { backgroundColor: '#fff' }, 400);

        var menu = new Ext.menu.Menu();

        if(this.store.getAt(rowIndex).get("public")) {
            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_menu_webbrowser",
                handler: this.openVersion.bind(this, rowIndex, grid)
            }));
        }

        menu.add(new Ext.menu.Item({
            text: t('edit'),
            iconCls: "pimcore_icon_menu_settings",
            handler: this.editVersion.bind(this, rowIndex, grid)
        }));

        if (this.document.isAllowed("publish")) {
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

        event.stopEvent();
        menu.showAt(event.getXY());
    },

    removeVersion: function (index, grid) {

        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        Ext.Ajax.request({
            url: "/admin/document/delete-version",
            params: {id: versionId}
        });

        grid.getStore().removeAt(index);
    },

    openVersion: function (index, grid) {
        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        window.open(this.document.data.path + this.document.data.key + '?v=' + versionId,'_blank');
    },

    editVersion: function (index, grid) {
        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        Ext.Ajax.request({
            url: "/admin/document/version-to-session",
            params: {id: versionId},
            success: this.reloadEdit.bind(this)
        });
    },

    publishVersion: function (index, grid) {
        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        Ext.Ajax.request({
            url: "/admin/document/publish-version",
            params: {id: versionId},
            success: function () {
                // reload document
                this.document.reload();
            }.bind(this)
        });
    },

    dataUpdate: function (store, record, operation) {

        if (operation == "edit") {
            Ext.Ajax.request({
                url: "/admin/document/version-update",
                params: {
                    data: Ext.encode(record.data)
                }
            });

            this.checkForPreview(store);
        }

        store.commitChanges();
    },

    reloadEdit: function () {
        this.document.edit.reload(true);

        // Open edit tab
        this.document.tabbar.setActiveTab(0);

    },

    reload: function () {
        this.store.reload();
    }

});