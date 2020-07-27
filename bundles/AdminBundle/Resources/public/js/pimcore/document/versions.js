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

pimcore.registerNS("pimcore.document.versions");
pimcore.document.versions = Class.create({

    initialize: function (document) {
        this.document = document;
    },

    getLayout: function () {

        if (this.layout == null) {

            var modelName = 'pimcore.model.documentversions';
            if (!Ext.ClassManager.get(modelName)) {
                Ext.define(modelName, {
                    extend: 'Ext.data.Model',
                    fields: ['id', 'date', 'note', {
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
                    }, "public", "show", "scheduled", {
                        name: 'publicurl', convert: function (v, rec) {
                            return this.document.data.path + this.document.data.key + "?v=" + rec.data.id;
                        }.bind(this)
                    }]

                });
            }

            this.store = new Ext.data.Store({
                autoDestroy: true,
                model: modelName,
                sorters: [
                    {
                        property: 'versionCount',
                        direction: 'DESC'
                    },
                    {
                        property: 'id',
                        direction: 'DESC'
                    }],
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_element_getversions'),
                    extraParams: {
                        id: this.document.id,
                        elementType: "document"
                    },
                    // Reader is now on the proxy, as the message was explaining
                    reader: {
                        type: 'json',
                        rootProperty: 'versions'
                    }
                }
            });

            this.store.on("update", this.dataUpdate.bind(this));

            var checkPublic = Ext.create('Ext.grid.column.Check', {
                text: t("public"),
                dataIndex: "public",
                width: 50
            });

            var checkShow = Ext.create('Ext.grid.column.Check', {
                text: t("show"),
                dataIndex: "show",
                width: 50
            });

            this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 2
            });


            this.grid = Ext.create('Ext.grid.Panel', {
                store: this.store,
                plugins: [this.cellEditing],
                columns: [
                    checkShow,
                    {
                        text: t("published"),
                        width: 50,
                        sortable: false,
                        dataIndex: 'date',
                        renderer: function (d, metaData, cellValues) {
                            var d = cellValues.get('date');
                            var versionCount = cellValues.get('versionCount');
                            var index = cellValues.get('index');

                            if (this.document.data.published && index === 0 && d == this.document.data.versionDate && versionCount == this.document.data.versionCount) {
                                metaData.tdCls = "pimcore_icon_publish";
                            }
                            return "";
                        }.bind(this),
                        editable: false
                    },
                    {
                        text: t("date"), width: 150, sortable: true, dataIndex: 'date', renderer: function (d) {
                            var date = new Date(d * 1000);
                            return Ext.Date.format(date, "Y-m-d H:i:s");
                        }, editable: false
                    },
                    {text: "ID", sortable: true, dataIndex: 'id', editable: false, width: 60},
                    {text: t("user"), sortable: true, dataIndex: 'name', editable: false},
                    {
                        text: t("scheduled"),
                        width: 130,
                        sortable: true,
                        dataIndex: 'scheduled',
                        renderer: function (d) {
                            if (d != null) {
                                var date = new Date(d * 1000);
                                return Ext.Date.format(date, "Y-m-d H:i:s");
                            }
                            return d;
                        },
                        editable: false
                    },
                    {text: t("note"), sortable: true, dataIndex: 'note', editor: new Ext.form.TextField(), renderer: Ext.util.Format.htmlEncode},
                    checkPublic,
                    {text: t("public_url"), width: 300, sortable: false, dataIndex: 'publicurl', editable: false}
                ],
                columnLines: true,
                trackMouseOver: true,
                stripeRows: true,
                width: 620,
                region: "west",
                split: true,
                viewConfig: {
                    xtype: 'patchedgridview'
                }
            });

            //this.grid.on("rowclick", this.onRowClick.bind(this));
            this.grid.on("rowcontextmenu", this.onRowContextmenu.bind(this));
            this.grid.on("beforerender", function () {
                this.store.load();
            }.bind(this));
            this.grid.reference = this;

            this.frameId = 'document_version_iframe_' + this.document.id;

            var preview = new Ext.Panel({
                title: t("preview"),
                region: "center",
                bodyCls: "pimcore_overflow_scrolling",
                html: '<iframe src="about:blank" frameborder="0" style="width:100%;" id="'
                    + this.frameId + '"></iframe>'
            });

            this.layout = new Ext.Panel({
                title: t('versions'),
                border: false,
                layout: "border",
                iconCls: "pimcore_material_icon_versions pimcore_material_icon",
                items: [this.grid, preview]
            });

            preview.on("resize", this.setLayoutFrameDimensions.bind(this));
        }

        return this.layout;
    },

    setLayoutFrameDimensions: function (el, width, height, rWidth, rHeight) {
        Ext.get(this.frameId).setStyle({
            height: (height - 38) + "px"
        });
    },

    checkForPreview: function (store) {

        var displayRecords = store.query("show", true);

        if (displayRecords.items) {
            var length = displayRecords.items.length;
            if (length > 0) {
                if (length == 1) {
                    this.showVersionPreview(displayRecords.getAt(0).data.id);
                } else if (length == 2) {
                    this.compareVersions(displayRecords.getAt(0).data.id, displayRecords.getAt(1).data.id);
                } else {
                    Ext.MessageBox.alert(t("error"), t("maximum_2_versions"));
                }
            }
        }
    },

    compareVersions: function (id1, id2) {
        var url = Routing.generate('pimcore_admin_document_document_diffversions', {from: id1, to: id2});;
        Ext.get(this.frameId).dom.src = url;
    },

    showVersionPreview: function (id) {
        var url = this.document.data.path + this.document.data.key + "?pimcore_version=" + id;
        Ext.get(this.frameId).dom.src = url;
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts) {

        var menu = new Ext.menu.Menu();

        menu.add(new Ext.menu.Item({
            text: t('open'),
            iconCls: "pimcore_icon_open",
            handler: this.openVersion.bind(this, rowIndex, grid)
        }));

        menu.add(new Ext.menu.Item({
            text: t('edit'),
            iconCls: "pimcore_icon_edit",
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

        menu.add(new Ext.menu.Item({
            text: t('clear_all'),
            iconCls: "pimcore_icon_delete",
            handler: this.removeAllVersion.bind(this, rowIndex, grid)
        }));

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },

    removeVersion: function (index, grid) {

        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_element_deleteversion'),
            method: 'DELETE',
            params: {id: versionId}
        });

        grid.getStore().removeAt(index);
    },

    removeAllVersion: function (index, grid) {
        var data = grid.getStore().getAt(index).data;
        var elememntId = data.cid;

        if (elememntId > 0) {
            Ext.Msg.confirm(t('clear_all'), t('clear_version_message'), function (btn) {
                if (btn == 'yes') {
                    var modificationDate = this.document.data.modificationDate;

                    Ext.Ajax.request({
                        url: Routing.generate('pimcore_admin_element_deleteallversion'),
                        method: 'DELETE',
                        params: {id: elememntId, date: modificationDate}
                    });

                    //get sub collection of versions for removel. Keep current version
                    var removeCollection = grid.getStore().getData().createFiltered(function (item) {
                        return item.get('date') != modificationDate;
                    });

                    grid.getStore().remove(removeCollection.getRange());
                }
            }.bind(this));
        }
    },

    openVersion: function (index, grid) {
        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        window.open(this.document.data.path + this.document.data.key + '?pimcore_version=' + versionId, '_blank');
    },

    editVersion: function (index, grid) {
        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_document_versiontosession'),
            method: 'POST',
            params: {id: versionId},
            success: this.reloadEdit.bind(this)
        });
    },

    publishVersion: function (index, grid) {
        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_document_publishversion'),
            method: "POST",
            params: {id: versionId},
            success: function(response) {
                var rdata = Ext.decode(response.responseText);

                if (rdata.success) {
                    this.document.reload();
                    pimcore.helpers.updateTreeElementStyle('document', this.document.id, rdata.treeData);
                } else {
                    Ext.MessageBox.alert(t("error"), rdata.message);
                }
            }.bind(this)
        });
    },

    dataUpdate: function (store, record, operation) {

        if (operation == "edit") {
            Ext.Ajax.request({
                method: "post",
                url: Routing.generate('pimcore_admin_element_versionupdate'),
                method: 'PUT',
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
