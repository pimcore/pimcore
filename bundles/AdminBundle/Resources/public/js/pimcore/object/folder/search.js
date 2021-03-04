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

pimcore.registerNS("pimcore.object.search");
pimcore.object.search = Class.create(pimcore.object.helpers.gridTabAbstract, {
    systemColumns: ["id", "fullpath", "type", "subtype", "filename", "classname", "creationDate", "modificationDate"],
    fieldObject: {},
    gridType: 'object',

    title: t('search_edit'),
    icon: "pimcore_material_icon_search pimcore_material_icon",
    onlyDirectChildren: false,

    sortinfo: {},
    initialize: function ($super, object, searchType) {
        $super();

        this.object = object;
        this.element = object;
        this.searchType = searchType;
        this.noBatchColumns = [];
        this.batchAppendColumns = [];
        this.batchRemoveColumns = [];
    },

    getLayout: function () {

        if (this.layout == null) {

            // check for classtypes inside of the folder if there is only one type don't display the selection
            var toolbarConfig;

            if (this.object.data.classes && typeof this.object.data.classes == "object") {

                if (this.object.data.classes.length < 1) {
                    return;
                }

                var data = [];
                for (i = 0; i < this.object.data.classes.length; i++) {
                    var klass = this.object.data.classes[i];
                    data.push([klass.id, klass.name, t(klass.name), klass.inheritance]);

                }

                var classStore = new Ext.data.ArrayStore({
                    data: data,
                    sorters: 'translatedText',
                    fields: [
                        {name: 'id', type: 'string'},
                        {name: 'name', type: 'string'},
                        {name: 'translatedText', type: 'string'},
                        {name: 'inheritance', type: 'bool'}
                    ]
                });


                this.classSelector = new Ext.form.ComboBox({
                    name: "selectClass",
                    listWidth: 'auto',
                    store: classStore,
                    queryMode: "local",
                    valueField: 'id',
                    displayField: 'translatedText',
                    triggerAction: 'all',
                    editable: true,
                    typeAhead: true,
                    forceSelection: true,
                    value: this.object.data["selectedClass"],
                    listeners: {
                        "select": this.changeClassSelect.bind(this)
                    }
                });

                if (this.object.data.classes.length > 1) {
                    toolbarConfig = [new Ext.Toolbar.TextItem({
                        text: t("please_select_a_type")
                    }), this.classSelector];
                }
                else {
                    this.currentClass = this.object.data.classes[0].id;
                    this.setClassInheritance(this.object.data.classes[0].inheritance);
                }
            }
            else {
                return;
            }

            this.layout = new Ext.Panel({
                title: this.title,
                border: false,
                layout: "fit",
                iconCls: this.icon,
                items: [],
                tbar: toolbarConfig
            });

            if (this.currentClass) {
                this.layout.on("afterrender", this.setClass.bind(this, this.currentClass));
            }
        }

        return this.layout;
    },

    changeClassSelect: function (field, newValue, oldValue) {
        var selectedClass = newValue.data.id;
        this.setClass(selectedClass);
        this.setClassInheritance(newValue.data.inheritance);
    },

    setClass: function (classId) {
        this.classId = classId;
        this.settings = {};
        this.getTableDescription();
    },

    setClassInheritance: function (inheritance) {
        this.object.data.general.allowInheritance = inheritance;
    },

    getTableDescription: function () {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_dataobjecthelper_gridgetcolumnconfig'),
            params: {
                id: this.classId,
                objectId:
                this.object.id,
                gridtype: "grid",
                gridConfigId: this.settings ? this.settings.gridConfigId : null,
                searchType: this.searchType
            },
            success: this.createGrid.bind(this, false)
        });
    },




    createGrid: function (fromConfig, response, settings, save, context) {
        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);

        var fields = [];

        if (response.responseText) {
            response = Ext.decode(response.responseText);

            if (response.pageSize) {
                itemsPerPage = response.pageSize;
            }

            fields = response.availableFields;
            this.gridLanguage = response.language;
            this.gridPageSize = response.pageSize;
            this.sortinfo = response.sortinfo;

            this.settings = response.settings || {};
            this.context = response.context || {};
            this.availableConfigs = response.availableConfigs;
            this.sharedConfigs = response.sharedConfigs;

            this.onlyDirectChildren = response.onlyDirectChildren;
            this.searchFilter = response.searchFilter;
            this.sqlFilter = response.sqlFilter;
        } else {
            itemsPerPage = this.gridPageSize;
            fields = response;
            this.settings = settings;
            this.context = context;
            this.buildColumnConfigMenu();
        }

        this.fieldObject = {};
        for (var i = 0; i < fields.length; i++) {
            this.fieldObject[fields[i].key] = fields[i];
        }

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1,
                listeners: {
                    beforeedit: function (editor, context, eOpts) {
                        //need to clear cached editors of cell-editing editor in order to
                        //enable different editors per row
                        var editors = editor.editors;
                        editors.each(function (editor) {
                            if (typeof editor.column.config.getEditor !== "undefined") {
                                Ext.destroy(editor);
                                editors.remove(editor);
                            }
                        });
                    }
                }
            }
        );

        // get current class
        var classStore = pimcore.globalmanager.get("object_types_store");
        var klass = classStore.getById(this.classId);

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
        });

        var gridHelper = new pimcore.object.helpers.grid(
            klass.data.text,
            fields,
            Routing.generate('pimcore_admin_dataobject_dataobject_gridproxy', {classId: this.classId, folderId: this.object.id}),
            baseParams,
            false
        );

        gridHelper.showSubtype = false;
        gridHelper.enableEditor = true;
        gridHelper.limit = itemsPerPage;

        var enableGridLocking = klass.get("enableGridLocking");

        this.store = gridHelper.getStore(this.noBatchColumns, this.batchAppendColumns, this.batchRemoveColumns);
        if (this.sortinfo) {
            this.store.sort(this.sortinfo.field, this.sortinfo.direction);
        }
        this.store.getProxy().setExtraParam("only_direct_children", this.onlyDirectChildren);
        this.store.setPageSize(itemsPerPage);

        if (existingFilters && fromConfig) {
            this.store.setFilters(existingFilters.items);
        }

        var gridColumns = gridHelper.getGridColumns();

        var needGridFilter = false;

        // gridfilter plugin does not load the store if there are no filter columns.
        // so if there are no filter columns, then don't add the plugin
        // however, in this case we have to load the store manually
        if (gridColumns) {
            for (let i = 0; i < gridColumns.length; i++) {
                let col = gridColumns[i];
                if (col.filter) {
                    needGridFilter = true;
                    break;
                }
            }
        }

        var plugins = [this.cellEditing];
        if (needGridFilter) {
            plugins.push('pimcore.gridfilters');
        }

        if (!needGridFilter) {
            this.store.load();
        }

        // grid
        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            store: this.store,
            columns: gridColumns,
            columnLines: true,
            enableLocking: enableGridLocking,
            stripeRows: true,
            bodyCls: "pimcore_editable_grid",
            border: true,
            selModel: gridHelper.getSelectionColumn(),
            trackMouseOver: true,
            loadMask: true,
            plugins: plugins,
            viewConfig: {
                forceFit: false,
                xtype: 'patchedgridview',
                enableTextSelection: true
            },
            listeners: {
                celldblclick: function(grid, td, cellIndex, record, tr, rowIndex, e, eOpts) {
                    var columns = grid.grid.getColumnManager().getColumns();
                    if (columns[cellIndex].dataIndex == 'id' || columns[cellIndex].dataIndex == 'fullpath') {
                        var data = this.store.getAt(rowIndex);
                        pimcore.helpers.openObject(data.get("id"), data.get("type"));
                    }
                }
            },
            cls: 'pimcore_object_grid_panel',
            tbar: this.getToolbar(fromConfig, save)
        });

        this.grid.on("columnmove", function () {
            this.saveColumnConfigButton.show()
        }.bind(this));
        this.grid.on("columnresize", function () {
            this.saveColumnConfigButton.show()
        }.bind(this));
        this.grid.on("lockcolumn", function () {
            this.saveColumnConfigButton.show()
        }.bind(this));
        this.grid.on("unlockcolumn", function () {
            this.saveColumnConfigButton.show()
        }.bind(this));

        this.grid.on("rowcontextmenu", this.onRowContextmenu);

        this.grid.on("afterrender", function (grid) {
            if (grid.enableLocking) {
                var grids = grid.items.items;
                for (var i = 0; i < grids.length; i++) {
                    this.updateGridHeaderContextMenu(grids[i]);
                }
            } else {
                this.updateGridHeaderContextMenu(grid);
            }
        }.bind(this));

        this.grid.on("sortchange", function (ct, column, direction, eOpts) {
            this.sortinfo = {
                field: column.dataIndex,
                direction: direction
            };
        }.bind(this));

        // check for filter updates
        this.grid.on("filterchange", function () {
            this.filterUpdateFunction(this.grid, this.toolbarFilterInfo, this.clearFilterButton);
        }.bind(this));

        gridHelper.applyGridEvents(this.grid);

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: itemsPerPage});

        this.editor = new Ext.Panel({
            layout: "border",
            items: [new Ext.Panel({
                items: [this.grid],
                region: "center",
                layout: "fit",
                bbar: this.pagingtoolbar
            })]
        });

        this.layout.removeAll();
        this.layout.add(this.editor);
        this.layout.updateLayout();

        if (save) {
            if (this.settings.isShared) {
                this.settings.gridConfigId = null;
            }
            this.saveConfig(false);
        }
    },

    getGridConfig: function ($super) {
        var config = $super();
        config.onlyDirectChildren = this.onlyDirectChildren;
        config.pageSize = this.pagingtoolbar.pageSize;
        config.searchFilter = this.searchField.getValue();
        config.sqlFilter = this.sqlEditor.getValue();
        config.onlyDirectChildren = this.checkboxOnlyDirectChildren.getValue();
        return config;
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts) {

        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);
        var selectedRows = grid.getSelectionModel().getSelection();

        if (selectedRows.length <= 1) {

            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: function (data) {
                    pimcore.helpers.openObject(data.data.id, "object");
                }.bind(this, data)
            }));

            if (pimcore.elementservice.showLocateInTreeButton("object")) {
                menu.add(new Ext.menu.Item({
                    text: t('show_in_tree'),
                    iconCls: "pimcore_icon_show_in_tree",
                    handler: function () {
                        try {
                            try {
                                pimcore.treenodelocator.showInTree(record.id, "object", this);
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
                hidden: data.data.locked,
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (data) {
                    var store = this.getStore();
                    var options = {
                        "elementType": "object",
                        "id": data.data.id,
                        "success": store.reload.bind(this.getStore())
                    };
                    pimcore.elementservice.deleteElement(options);
                }.bind(grid, data)
            }));
        } else {
            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: function (data) {
                    var selectedRows = grid.getSelectionModel().getSelection();
                    for (var i = 0; i < selectedRows.length; i++) {
                        pimcore.helpers.openObject(selectedRows[i].data.id, "object");
                    }
                }.bind(this, data)
            }));

            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (data) {
                    var ids = [];
                    var selectedRows = grid.getSelectionModel().getSelection();
                    for (var i = 0; i < selectedRows.length; i++) {
                        ids.push(selectedRows[i].data.id);
                    }
                    ids = ids.join(',');

                    var options = {
                        "elementType": "object",
                        "id": ids,
                        "success": function () {
                            this.getStore().reload();
                            var tree = pimcore.globalmanager.get("layout_object_tree");
                            var treePanel = tree.tree;
                            tree.refresh(treePanel.getRootNode());
                        }.bind(this)
                    };
                    pimcore.elementservice.deleteElement(options);
                }.bind(grid, data)
            }));
        }

        pimcore.plugin.broker.fireEvent("prepareOnRowContextmenu", menu, this, selectedRows);

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    }


});

pimcore.object.search.addMethods(pimcore.element.helpers.gridColumnConfig);
