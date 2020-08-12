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

pimcore.registerNS("pimcore.object.versions");
pimcore.object.versions = Class.create({

    initialize: function (object) {
        this.object = object;
    },

    getLayout: function () {

        if (this.layout == null) {

            var modelName = 'pimcore.model.objectversions';
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
                    }, 'versionCount']
                });
            }

            this.store = new Ext.data.Store({
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
                        id: this.object.id,
                        elementType: "object"
                    },
                    // Reader is now on the proxy, as the message was explaining
                    reader: {
                        type: 'json',
                        rootProperty: 'versions'
                    }

                }
            });

            this.store.on("update", this.dataUpdate.bind(this));

            this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 2
            });

            var grid = Ext.create('Ext.grid.Panel', {
                store: this.store,
                plugins: [this.cellEditing],
                columns: [
                    {
                        text: t("published"),
                        width: 50,
                        sortable: false,
                        dataIndex: 'id',
                        renderer: function (d, metaData, cellValues) {
                            var d = cellValues.get('date');
                            var versionCount = cellValues.get('versionCount');
                            var index = cellValues.get('index');
                            if (this.object.data.general.o_published && index === 0 && d == this.object.data.general.versionDate && versionCount == this.object.data.general.versionCount) {
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
                        }
                    },
                    {text: "ID", sortable: true, dataIndex: 'id', editable: false, width: 60},
                    {text: t("user"), sortable: true, dataIndex: 'name'},
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
                        },
                        editable: false
                    },
                    {text: t("note"), sortable: true, dataIndex: 'note', editor: new Ext.form.TextField(), renderer: Ext.util.Format.htmlEncode}
                ],
                stripeRows: true,
                width: 450,
                title: t("press_crtl_and_select_to_compare"),
                region: "west",
                split: true,
                selModel: new Ext.selection.RowModel({
                    mode: 'MULTI'
                }),
                viewConfig: {
                    xtype: 'patchedgridview',
                    enableTextSelection: true
                }
            });

            grid.on("rowclick", this.onRowClick.bind(this));
            grid.on("rowcontextmenu", this.onRowContextmenu.bind(this));
            grid.on("beforerender", function () {
                this.store.load();
            }.bind(this));

            grid.reference = this;

            this.iframeId = 'object_version_iframe_' + this.object.id;

            var preview = new Ext.Panel({
                title: t("preview"),
                region: "center",
                bodyCls: "pimcore_overflow_scrolling",
                html: '<iframe src="about:blank" frameborder="0" style="width:100%;" id="' + this.iframeId + '"></iframe>'
            });

            this.layout = new Ext.Panel({
                title: t('versions'),
                bodyStyle: 'padding:20px 5px 20px 5px;',
                border: false,
                layout: "border",
                iconCls: "pimcore_material_icon_versions pimcore_material_icon",
                items: [grid, preview]
            });

            preview.on("resize", this.setLayoutFrameDimensions.bind(this));
        }

        return this.layout;
    },

    setLayoutFrameDimensions: function (el, width, height, rWidth, rHeight) {
        Ext.get(this.iframeId).setStyle({
            height: (height - 38) + "px"
        });
    },

    onRowClick: function (grid, record, tr, rowIndex, e, eOpts) {
        var selModel = grid.getSelectionModel();
        if (selModel.getCount() > 2) {
            selModel.select(record);
        }

        if (selModel.getCount() > 1) {
            this.compareVersions(grid, rowIndex, e);
        } else {
            this.showVersionPreview(grid, rowIndex, e);
        }
    },

    compareVersions: function (grid, rowIndex, event) {
        if (grid.getSelectionModel().getCount() < 3) {

            var selections = grid.getSelectionModel().getSelection();

            var url = Routing.generate('pimcore_admin_dataobject_dataobject_diffversions', {from: selections[0].data.id, to: selections[1].data.id});
            Ext.get(this.iframeId).dom.src = url;
        }
    },

    showVersionPreview: function (grid, rowIndex, event) {

        var store = grid.getStore();
        var data = store.getAt(rowIndex).data;
        var versionId = data.id;

        var url = Routing.generate('pimcore_admin_dataobject_dataobject_previewversion', {id: versionId});
        Ext.get(this.iframeId).dom.src = url;
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts) {

        var menu = new Ext.menu.Menu();

        if (this.object.isAllowed("publish")) {
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
                    var modificationDate = this.object.data.general.o_modificationDate;

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

    publishVersion: function (index, grid) {
        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_dataobject_publishversion'),
            method: "POST",
            params: {id: versionId},
            success: function (response) {
                var rdata = Ext.decode(response.responseText);

                if (rdata.success) {
                    this.object.reload();

                    pimcore.helpers.updateTreeElementStyle('object', this.object.id, rdata.treeData);
                } else {
                    Ext.MessageBox.alert(t("error"), rdata.message);
                }

            }.bind(this)
        });
    },

    reload: function () {
        this.store.reload();
    },

    dataUpdate: function (store, record, operation) {

        if (operation == "edit") {
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_element_versionupdate'),
                method: 'PUT',
                params: {
                    data: Ext.encode(record.data)
                }
            });
        }

        store.commitChanges();
    }


});
