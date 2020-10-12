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

pimcore.registerNS("pimcore.object.variantsTab");
pimcore.object.variantsTab = Class.create(pimcore.object.helpers.gridTabAbstract, {
    systemColumns: ["id", "fullpath"],
    objecttype: "variant",
    gridType: 'object',

    fieldObject: {},
    initialize: function ($super, object) {
        $super();

        this.element = object;
        this.searchType = "folder";
        this.noBatchColumns = [];
        this.batchAppendColumns = [];
        this.batchRemoveColumns = [];
    },

    getLayout: function () {
        this.selectedClass = this.element.data.general.o_className;

        var classStore = pimcore.globalmanager.get("object_types_store");
        var klassIndex = classStore.findExact("text", this.selectedClass);
        var klass = classStore.getAt(klassIndex);
        this.classId = klass.id;
        this.object = this.element;

        if (this.layout == null) {
            this.getTableDescription()


            this.layout = new Ext.Panel({
                title: t('variants'),
                border: false,
                iconCls: "pimcore_material_icon_variants pimcore_material_icon",
                layout: "fit"
            });
        }

        return this.layout;
    },


    getTableDescription: function () {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_dataobjecthelper_gridgetcolumnconfig'),
            params: {
                id: this.classId,
                objectId:
                this.element.id,
                gridtype: "grid",
                gridConfigId: this.settings ? this.settings.gridConfigId : null,
                searchType: this.searchType
            },
            success: this.createGrid.bind(this, false)
        });
    },

    createGrid: function (fromConfig, response, settings, save, context) {

        this.context = context;
        var fields = [];
        if (response.responseText) {
            response = Ext.decode(response.responseText);
            fields = response.availableFields;
            this.gridLanguage = response.language;
            this.sortinfo = response.sortinfo;

            this.settings = response.settings || {};
            this.context = response.context || {};
            this.availableConfigs = response.availableConfigs;
            this.sharedConfigs = response.sharedConfigs;
        } else {
            fields = response;
            this.settings = settings;
            this.context = context;
            this.buildColumnConfigMenu();
        }

        this.fieldObject = {};
        for (var i = 0; i < fields.length; i++) {
            this.fieldObject[fields[i].key] = fields[i];
        }

        var baseParams;

        var existingFilters;
        if (this.store) {
            existingFilters = this.store.getFilters();
            baseParams = this.store.getProxy().getExtraParams();
        } else {
            baseParams = {};
        }

        Ext.apply(baseParams, {
            language: this.gridLanguage,
            objectId: this.element.id
        });

        var gridHelper = new pimcore.object.helpers.grid(
            this.selectedClass,
            fields,
            Routing.generate('pimcore_admin_dataobject_variants_getvariants'),
            baseParams,
            false
        );

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);

        gridHelper.showSubtype = false;
        gridHelper.showKey = true;
        gridHelper.enableEditor = true;
        gridHelper.baseParams.objectId = this.element.id;

        this.store = gridHelper.getStore(this.noBatchColumns, this.batchAppendColumns, this.batchRemoveColumns);
        this.store.setPageSize(itemsPerPage);

        if (existingFilters && fromConfig) {
            this.store.setFilters(existingFilters.items);
        }

        var gridColumns = gridHelper.getGridColumns();

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            menuText: t('open'),
            width: 30,
            items: [
                {
                    tooltip: t('open'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/open_file.svg",
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
            menuText: t('remove'),
            width: 30,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                    isDisabled: function(view, rowIndex, colIndex, item, record) {
                        return record.data.locked || !record.data.permissions.delete;
                    },
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        Ext.MessageBox.confirm(' ', t('delete_message'),
                                                    this.doDeleteVariant.bind(this, data.id), this);
                    }.bind(this)
                }
            ]
        });

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: itemsPerPage});


        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        var plugins = [this.cellEditing, 'gridfilters'];

        let tbar = this.getToolbar(fromConfig, save);
        tbar.insert(0,{
            text: t('add_variant'),
            handler: this.onAdd.bind(this),
            iconCls: "pimcore_icon_add"
        });

        tbar.insert(1, '-');


        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            store: this.store,
            border: true,
            columns: gridColumns,
            columnLines: true,
            plugins: plugins,
            stripeRows: true,
            cls: 'pimcore_object_grid_panel',
            bodyCls: "pimcore_editable_grid",
            trackMouseOver: true,
            viewConfig: {
                forceFit: false,
                xtype: 'patchedgridview',
                enableTextSelection: true
            },
            selModel: gridHelper.getSelectionColumn(),
            bbar: this.pagingtoolbar,
            tbar: tbar,
            listeners: {
                rowdblclick: function (grid, record, tr, rowIndex, e, eOpts) {

                }.bind(this)
            }
        });

        this.grid.on("columnmove", function () {
            this.saveColumnConfigButton.show()
        }.bind(this));
        this.grid.on("columnresize", function () {
            this.saveColumnConfigButton.show()
        }.bind(this));

        this.grid.on("rowcontextmenu", this.onRowContextmenu.bind(this));

        this.grid.on("afterrender", function (grid) {
            this.updateGridHeaderContextMenu(grid);
        }.bind(this));

        this.grid.on("sortchange", function (grid, sortinfo) {
            this.sortinfo = sortinfo;
        }.bind(this));

        // check for filter updates
        this.grid.on("filterchange", function () {
            this.filterUpdateFunction(this.grid, this.toolbarFilterInfo, this.clearFilterButton);
        }.bind(this));

        gridHelper.applyGridEvents(this.grid);

        this.store.load();

        this.layout.removeAll();
        this.layout.add(this.grid);
        this.layout.updateLayout();

        if (save) {
            if (this.settings.isShared) {
                this.settings.gridConfigId = null;
            }
            this.saveConfig(false);
        }

    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts) {
        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);

        if (record.data.permissions.rename && !record.data.locked) {
            menu.add(new Ext.menu.Item({
                text: t('rename'),
                iconCls: "pimcore_icon_key pimcore_icon_overlay_go",
                handler: function (data) {
                    Ext.MessageBox.prompt(t('rename'), t('please_enter_the_new_name'),
                        this.editKey.bind(this, data.id), null, null, data.data.filename);
                }.bind(this, data),
            }));

            e.stopEvent();
            menu.showAt(e.getXY());
        }
    },

    editKey: function (id, button, value) {
        if (button == "ok") {
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_variants_updatekey'),
                method: 'PUT',
                params: {id: id, key: value},
                success: function (response) {
                    this.store.reload();
                    var responseJson = Ext.decode(response.responseText);
                    if (!responseJson.success) {
                        pimcore.helpers.showNotification(t("error"), t("error_renaming_item"), "error",
                            t(responseJson.message));
                    }
                }.bind(this)
            });
        }
    },


    onAdd: function (btn, ev) {
        Ext.MessageBox.prompt(t('add_variant'), t('enter_the_name_of_the_new_item'), this.doAdd.bind(this));
    },

    doAdd: function (button, value) {
        if (button == "ok") {
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_dataobject_add'),
                method: 'POST',
                params: {
                    className: this.element.data.general.o_className,
                    classId: this.element.data.general.o_classId,
                    parentId: this.element.id,
                    objecttype: "variant",
                    key: pimcore.helpers.getValidFilename(value, "object")
                },
                success: function (response) {
                    var responseJson = Ext.decode(response.responseText);
                    if (responseJson.success) {
                        this.store.reload();
                        pimcore.helpers.openObject(responseJson.id, responseJson.type);

                        pimcore.elementservice.refreshNodeAllTrees("object", this.element.id);
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error",
                            t(responseJson.message));
                    }
                }.bind(this)
            });
        }
    },


    doDeleteVariant: function (id, answer) {
        if (answer == "yes") {
            if (pimcore.globalmanager.exists("object_" + id)) {
                var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                tabPanel.remove("object_" + id);
            }

            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_dataobject_delete'),
                method: 'DELETE',
                params: {
                    id: id
                },
                success: function (response) {
                    try {
                        //Ext.get(this.getUI().getIconEl()).dom.setAttribute("class", this.originalClass);
                        var rdata = Ext.decode(response.responseText);
                        if (rdata && !rdata.success) {
                            pimcore.helpers.showNotification(t("error"), t("error_deleting_item"), "error",
                                t(rdata.message));
                        }
                    } catch (e) {
                        pimcore.helpers.showNotification(t("error"), t("error_deleting_item"), "error");
                    }
                    this.store.reload();
                    pimcore.elementservice.refreshNodeAllTrees("object", this.element.id);
                }.bind(this)
            });
        }
    }

});

pimcore.object.variantsTab.addMethods(pimcore.element.helpers.gridColumnConfig);
