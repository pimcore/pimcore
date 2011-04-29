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

pimcore.registerNS("pimcore.object.variantsTab");
pimcore.object.variantsTab = Class.create({

    initialize: function(element) {
        this.element = element;

    },

    getLayout: function () {
        this.selectedClass = this.element.data.general.o_className;
        if (this.layout == null) {

            Ext.Ajax.request({
                url: "/admin/object/grid-get-column-config",
                params: {name: this.selectedClass},
                success: this.createGrid.bind(this)
            });

            this.layout = new Ext.Panel({
                title: t('variants'),
                border: false,
                iconCls: "pimcore_icon_tab_variants",
                layout: "fit"
            });
        }

        return this.layout;
    },

    createGrid: function(response) {
        var fields = Ext.decode(response.responseText);
        var gridHelper = new pimcore.object.helpers.grid(this.selectedClass, fields, "/admin/variants/get-variants",false);
        gridHelper.showSubtype = false;
        gridHelper.showKey = true;
        gridHelper.baseParams.objectId = this.element.id;

        this.store = gridHelper.getStore();
        var gridColumns = gridHelper.getGridColumns();

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            width: 30,
            items: [
                {
                    tooltip: t('open'),
                    icon: "/pimcore/static/img/icon/pencil_go.png",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        pimcore.helpers.openObject(data.id, "variant");
                    }.bind(this)
                }
            ]
        });
        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            width: 30,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/pimcore/static/img/icon/cross.png",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        Ext.MessageBox.confirm(t('remove_variant'), t('remove_variant_text'), this.doDeleteVariant.bind(this, data.id), this);
                    }.bind(this)
                }
            ]
        });



        var gridfilters = gridHelper.getGridFilters();

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: 15,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_objects_found")
        });

        this.gridPanel = new Ext.grid.GridPanel({
            store: this.store,
            border: false,
            columns: gridColumns,
            loadMask: true,
            columnLines: true,
            plugins: [gridfilters],
            stripeRows: true,
            viewConfig: {
                forceFit: false
            },
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            bbar: this.pagingtoolbar,
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                },
                '-'
            ],
            listeners: {
                rowdblclick: function (grid, rowIndex, ev) {

                }.bind(this)
            }
        });
        this.gridPanel.on("rowcontextmenu", this.onRowContextmenu.bind(this));
        this.store.load();

        this.layout.add(this.gridPanel);
    },


    onRowContextmenu: function (grid, rowIndex, event) {
        $(grid.getView().getRow(rowIndex)).animate( { backgroundColor: '#E0EAEE' }, 100).animate( { backgroundColor: '#fff' }, 400);

        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);

        menu.add(new Ext.menu.Item({
            text: t('rename'),
            iconCls: "pimcore_icon_edit_key",
            handler: function (data) {
                Ext.MessageBox.prompt(t('rename'), t('please_enter_the_new_name'), this.editKey.bind(this, data.id), null, null, data.data.filename);
            }.bind(this, data)
        }));

        event.stopEvent();
        menu.showAt(event.getXY());
    },

    editKey: function (id, button, value) {
        if (button == "ok") {
            Ext.Ajax.request({
                url: "/admin/variants/update-key",
                params: {id: id, key: value},
                success: function(response) {
                    this.store.reload();
                    var responseJson = Ext.decode(response.responseText);
                    if(!responseJson.success) {
                        pimcore.helpers.showNotification(t("error"), t("error_renaming_variant"), "error", t(responseJson.message));
                    }
                }.bind(this)
            });
        }
    },


    onAdd: function (btn, ev) {
        Ext.MessageBox.prompt(t('add_variant'), t('please_enter_the_name_of_the_new_variant'), this.doAdd.bind(this));
    },

    doAdd: function(button, value) {
        if (button == "ok") {
            Ext.Ajax.request({
                url: "/admin/object/add",
                params: {
                    className: this.element.data.general.o_className,
                    classId: this.element.data.general.o_classId,
                    parentId: this.element.id,
                    objecttype: "variant",
                    key: pimcore.helpers.getValidFilename(this.element.data.general.o_key + "_" + value)
                },
                success: function(response) {
                    var responseJson = Ext.decode(response.responseText);
                    if(responseJson.success) {
                        this.store.reload();
                        pimcore.helpers.openObject(responseJson.id, responseJson.type);
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("error_creating_variant"), "error", t(responseJson.message));
                    }
                }.bind(this)
            });
        }
    },

    doDeleteVariant: function(id, answer) {
        if(answer == "yes") {
            if (pimcore.globalmanager.exists("object_" + id)) {
                var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                tabPanel.remove("object_" + id);
            }

            Ext.Ajax.request({
                url: "/admin/object/delete",
                params: {
                    id: id
                },
                success: function (response) {
                    try {
                        //Ext.get(this.getUI().getIconEl()).dom.setAttribute("class", this.originalClass);
                        var rdata = Ext.decode(response.responseText);
                        if (rdata && !rdata.success) {
                            pimcore.helpers.showNotification(t("error"), t("error_deleting_variant"), "error", t(rdata.message));
                        }
                    } catch(e) {
                        pimcore.helpers.showNotification(t("error"), t("error_deleting_variant"), "error");
                    }
                    this.store.reload();
                }.bind(this)
            });
        }
    }

});