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

pimcore.registerNS("pimcore.object.search");
pimcore.object.search = Class.create(pimcore.object.helpers.gridTabAbstract, {
    systemColumns: ["id", "fullpath", "type", "subtype", "filename", "classname", "creationDate", "modificationDate"],
    fieldObject: {},

    title: t('search_edit'),
    icon: "pimcore_icon_search",
    onlyDirectChildren: false,

    sortinfo: {},
    initialize: function(object, searchType) {
        this.object = object;
        this.element = object;
        this.searchType = searchType;
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
                    data.push([klass.id, klass.name, ts(klass.name)]);

                }

                var classStore = new Ext.data.ArrayStore({
                    data: data,
                    sortInfo: {
                        field: 'translatedText',
                        direction: 'ASC'
                    },
                    fields: [
                        {name: 'id', type: 'number'},
                        {name: 'name', type: 'string'},
                        {name: 'translatedText', type: 'string'}
                    ]
                });


                this.classSelector = new Ext.form.ComboBox({
                    name: "selectClass",
                    listWidth: 'auto',
                    store: classStore,
                    queryMode:"local",
                    valueField: 'id',
                    displayField: 'translatedText',
                    triggerAction: 'all',
                    editable: true,
                    typeAhead:true,
                    forceSelection: true,
                    value: this.object.data["selectedClass"],
                    listeners: {
                        "select": this.changeClassSelect.bind(this)
                    }
                });

                if (this.object.data.classes.length > 1) {
                    toolbarConfig = [new Ext.Toolbar.TextItem({
                        text: t("please_select_a_type")
                    }),this.classSelector];
                }
                else {
                    this.currentClass = this.object.data.classes[0].id;
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
    },

    setClass: function (classId) {
        this.classId = classId;
        this.getTableDescription(classId);
    },

    getTableDescription: function (classId) {
        Ext.Ajax.request({
            url: "/admin/object-helper/grid-get-column-config",
            params: {
                id: classId,
                objectId:
                this.object.id,
                gridtype: "grid",
                searchType: this.searchType
            },
            success: this.createGrid.bind(this, false)
        });
    },

    createGrid: function (fromConfig, response) {
        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);

        var fields = [];
        if (response.responseText) {
            response = Ext.decode(response.responseText);

            if (response.pageSize) {
                itemsPerPage = response.pageSize;
            }

            fields = response.availableFields;
            this.gridLanguage = response.language;
            this.sortinfo = response.sortinfo;
            if (response.onlyDirectChildren) {
                this.onlyDirectChildren = response.onlyDirectChildren;
            }
        } else {
            fields = response;
        }

        this.fieldObject = {};
        for (var i = 0; i < fields.length; i++) {
            this.fieldObject[fields[i].key] = fields[i];
        }

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        var plugins = [this.cellEditing, 'pimcore.gridfilters'];

        // get current class
        var classStore = pimcore.globalmanager.get("object_types_store");
        var klass = classStore.getById(this.classId);

        var gridHelper = new pimcore.object.helpers.grid(
            klass.data.text,
            fields,
            "/admin/object/grid-proxy/classId/" + this.classId + "/folderId/" + this.object.id,
            {
                language: this.gridLanguage,
                // limit: itemsPerPage
            },
            false
        );

        gridHelper.showSubtype = false;
        gridHelper.enableEditor = true;
        gridHelper.limit = itemsPerPage;


        var propertyVisibility = klass.get("propertyVisibility");

        this.store = gridHelper.getStore();
        if (this.sortinfo) {
            this.store.sort(this.sortinfo.field, this.sortinfo.direction);
        }
        this.store.getProxy().setExtraParam("only_direct_children", this.onlyDirectChildren);
        this.store.setPageSize(itemsPerPage);

        var gridColumns = gridHelper.getGridColumns();

        // add filters
        this.gridfilters = gridHelper.getGridFilters();

        this.languageInfo = new Ext.Toolbar.TextItem({
            text: t("grid_current_language") + ": " + (this.gridLanguage == "default" ? t("default") : pimcore.available_languages[this.gridLanguage])
        });

        this.toolbarFilterInfo =  new Ext.Button({
            iconCls: "pimcore_icon_filter_condition",
            hidden: true,
            text: '<b>' + t("filter_active") + '</b>',
            tooltip: t("filter_condition"),
            handler: function (button) {
                Ext.MessageBox.alert(t("filter_condition"), button.pimcore_filter_condition);
            }.bind(this)
        });

        this.clearFilterButton =  new Ext.Button({
            iconCls: "pimcore_icon_clear_filters",
            hidden: true,
            text: t("clear_filters"),
            tooltip: t("clear_filters"),
            handler: function (button) {
                this.grid.filters.clearFilters();
                this.toolbarFilterInfo.hide();
                this.clearFilterButton.hide();
            }.bind(this)
        });


        this.createSqlEditor();

        this.checkboxOnlyDirectChildren = new Ext.form.Checkbox({
            name: "onlyDirectChildren",
            style: "margin-bottom: 5px; margin-left: 5px",
            checked: this.onlyDirectChildren,
            boxLabel: t("only_children"),
            listeners: {
                "change": function (field, checked) {
                    this.grid.filters.clearFilters();

                    this.store.getProxy().setExtraParam("only_direct_children", checked);

                    this.onlyDirectChildren = checked;
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        });

        var hideSaveColumnConfig = !fromConfig;

        this.saveColumnConfigButton = new Ext.Button({
            tooltip: t('save_column_configuration'),
            iconCls: "pimcore_icon_publish",
            hidden: hideSaveColumnConfig,
            handler: function() {
                pimcore.helpers.saveColumnConfig(this.object.id, this.classId, this.getGridConfig(), this.searchType, this.saveColumnConfigButton);
            }.bind(this)
        });

        // grid
        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            store: this.store,
            columns: gridColumns,
            columnLines: true,
            stripeRows: true,
            bodyCls: "pimcore_editable_grid",
            border: true,
            selModel: gridHelper.getSelectionColumn(),
            trackMouseOver: true,
            loadMask: true,
            plugins: plugins,
            viewConfig: {
                forceFit: false,
                xtype: 'patchedgridview'
            },
            cls: 'pimcore_object_grid_panel',
            tbar: [this.languageInfo, "-", this.toolbarFilterInfo, this.clearFilterButton, "->", this.checkboxOnlyDirectChildren, "-", this.sqlEditor, this.sqlButton, "-", {
                text: t("search_and_move"),
                iconCls: "pimcore_icon_search pimcore_icon_overlay_go",
                handler: pimcore.helpers.searchAndMove.bind(this, this.object.id,
                    function () {
                        this.store.reload();
                    }.bind(this), "object")
            }, "-", {
                text: t("export_csv"),
                iconCls: "pimcore_icon_export",
                handler: function () {

                    Ext.MessageBox.show({
                        title: t('warning'),
                        msg: t('csv_object_export_warning'),
                        buttons: Ext.Msg.OKCANCEL,
                        fn: function (btn) {
                            if (btn == 'ok') {
                                this.exportPrepare();
                            }
                        }.bind(this),
                        icon: Ext.MessageBox.WARNING
                    });


                }.bind(this)
            }, "-", {
                text: t("grid_column_config"),
                iconCls: "pimcore_icon_table_col pimcore_icon_overlay_edit",
                handler: this.openColumnConfig.bind(this)
            },
                this.saveColumnConfigButton
            ]
        });
        this.grid.on("rowcontextmenu", this.onRowContextmenu);

        this.grid.on("afterrender", function (grid) {
            this.updateGridHeaderContextMenu(grid);
        }.bind(this));

        this.grid.on("sortchange", function (ct, column, direction, eOpts ) {
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
    },


    getGridConfig: function($super) {
        var config = $super();
        config.onlyDirectChildren = this.onlyDirectChildren;
        config.pageSize = this.pagingtoolbar.pageSize;
        return config;
    },


    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {

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
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (data) {
                    var store = this.getStore();
                    var options = {
                        "elementType" : "object",
                        "id": data.data.id,
                        "success": store.reload.bind(this.getStore())
                    };
                    pimcore.elementservice.deleteElement(options);
                }.bind(grid, data)
            }));
        } else {
            menu.add(new Ext.menu.Item({
                text: t('open_selected'),
                iconCls: "pimcore_icon_open",
                handler: function (data) {
                    var selectedRows = grid.getSelectionModel().getSelection();
                    for (var i = 0; i < selectedRows.length; i++) {
                        pimcore.helpers.openObject(selectedRows[i].data.id, "object");
                    }
                }.bind(this, data)
            }));

            menu.add(new Ext.menu.Item({
                text: t('delete_selected'),
                iconCls: "pimcore_icon_delete",
                handler: function (data) {
                    var ids = [];
                    var selectedRows = grid.getSelectionModel().getSelection();
                    for (var i = 0; i < selectedRows.length; i++) {
                        ids.push(selectedRows[i].data.id);
                    }
                    ids = ids.join(',');

                    var options = {
                        "elementType" : "object",
                        "id": ids,
                        "success": function() {
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
