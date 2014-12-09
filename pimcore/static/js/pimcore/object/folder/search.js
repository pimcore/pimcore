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

pimcore.registerNS("pimcore.object.search");
pimcore.object.search = Class.create(pimcore.object.helpers.gridTabAbstract, {
    systemColumns: ["id", "fullpath", "type", "subtype", "filename", "classname", "creationDate", "modificationDate"],
    fieldObject: {},

    title: t('search_edit'),
    icon: "pimcore_icon_tab_search",
    onlyDirectChildren: false,

    sortInfo: {},
    initialize: function(object) {
        this.object = object;
        this.element = object;
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
                    mode:"local",
                    valueField: 'id',
                    displayField: 'translatedText',
                    triggerAction: 'all',
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
            params: {id: classId, objectId: this.object.id, gridtype: "grid"},
            success: this.createGrid.bind(this)
        });
    },

    createGrid: function (response) {

        var itemsPerPage = 20;

        var fields = [];
        if(response.responseText) {
            response = Ext.decode(response.responseText);

            if (response.pageSize) {
                itemsPerPage = response.pageSize;
            }

            fields = response.availableFields;
            this.gridLanguage = response.language;
            this.sortinfo = response.sortinfo;
            if(response.onlyDirectChildren) {
                this.onlyDirectChildren = response.onlyDirectChildren;
            }
        } else {
            fields = response;
        }

        this.fieldObject = {};
        for(var i = 0; i < fields.length; i++) {
            this.fieldObject[fields[i].key] = fields[i];
        }

        var plugins = [];

        // get current class
        var classStore = pimcore.globalmanager.get("object_types_store");
        var klass = classStore.getById(this.classId);

        var gridHelper = new pimcore.object.helpers.grid(
            klass.data.text,
            fields,
            "/admin/object/grid-proxy/classId/" + this.classId + "/folderId/" + this.object.id,
            {language: this.gridLanguage,
             limit: itemsPerPage
            },
            false
        );

        gridHelper.showSubtype = false;
        gridHelper.enableEditor = true;
        gridHelper.limit = itemsPerPage;


        var propertyVisibility = klass.get("propertyVisibility");

        this.store = gridHelper.getStore();
        if(this.sortinfo) {
            this.store.setDefaultSort(this.sortinfo.field, this.sortinfo.direction);
        }
        this.store.setBaseParam("only_direct_children", this.onlyDirectChildren);
        this.store.load();

        var gridColumns = gridHelper.getGridColumns();


        // add filters
        this.gridfilters = gridHelper.getGridFilters();

        plugins.push(this.gridfilters);


        this.languageInfo = new Ext.Toolbar.TextItem({
            text: t("grid_current_language") + ": " + pimcore.available_languages[this.gridLanguage]
        });

        this.toolbarFilterInfo = new Ext.Toolbar.TextItem({
            text: ""
        });

        this.createSqlEditor();

        this.checkboxOnlyDirectChildren = new Ext.form.Checkbox({
            name: "onlyDirectChildren",
            style: "margin-bottom: 5px; margin-left: 5px",
            checked: this.onlyDirectChildren,
            listeners: {
                "check" : function (field, checked) {
                    this.gridfilters.clearFilters();

                    this.store.baseparams = {};
                    this.store.setBaseParam("only_direct_children", checked);

                    this.onlyDirectChildren = checked;
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        });

        // grid
        this.grid = new Ext.grid.EditorGridPanel({
            frame: false,
            store: this.store,
            columns : gridColumns,
            columnLines: true,
            stripeRows: true,
            plugins: plugins,
            border: true,
            sm: gridHelper.getSelectionColumn(),
            trackMouseOver: true,
            loadMask: true,
            viewConfig: {
                forceFit: false
            },
            tbar: [this.languageInfo, "-", this.toolbarFilterInfo
                ,"->"
                ,this.checkboxOnlyDirectChildren,t("only_children")
                ,"-",this.sqlEditor
                ,this.sqlButton,"-",{
                    text: t("search_and_move"),
                    iconCls: "pimcore_icon_search_and_move",
                    handler: pimcore.helpers.searchAndMove.bind(this, this.object.id,
                        function() {
                            this.store.reload();
                        }.bind(this), "object")
                },"-",{
                    text: t("export_csv"),
                    iconCls: "pimcore_icon_export",
                    handler: function(){

                        Ext.MessageBox.show({
                            title:t('warning'),
                            msg: t('csv_object_export_warning'),
                            buttons: Ext.Msg.OKCANCEL ,
                            fn: function(btn){
                                if (btn == 'ok'){
                                    this.startCsvExport();
                                }
                            }.bind(this),
                            icon: Ext.MessageBox.WARNING
                        });



                    }.bind(this)
                },"-",{
                    text: t("grid_column_config"),
                    iconCls: "pimcore_icon_grid_column_config",
                    handler: this.openColumnConfig.bind(this)
                }]
        });
        this.grid.on("rowcontextmenu", this.onRowContextmenu);

        this.grid.on("afterrender", function (grid) {
            this.updateGridHeaderContextMenu(grid);
        }.bind(this));

        this.grid.on("sortchange", function(grid, sortinfo) {
            this.sortinfo = sortinfo;
        }.bind(this));

        // check for filter updates
        this.grid.on("filterupdate", function () {
            this.filterUpdateFunction(this.gridfilters, this.toolbarFilterInfo);
        }.bind(this));

        gridHelper.applyGridEvents(this.grid);

        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: itemsPerPage,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_objects_found")
        });


        // add per-page selection
        this.pagingtoolbar.add("-");

        this.pagingtoolbar.add(new Ext.Toolbar.TextItem({
            text: t("items_per_page")
        }));
        this.pagingtoolbar.add(new Ext.form.ComboBox({
            store: [
                [10, "10"],
                [20, "20"],
                [40, "40"],
                [60, "60"],
                [80, "80"],
                [100, "100"],
                [999999, t("all")]
            ],
            mode: "local",
            width: 50,
            value: itemsPerPage,
            triggerAction: "all",
            listeners: {
                select: function (box, rec, index) {
                    this.pagingtoolbar.pageSize = intval(rec.data.field1);
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        }));

        this.editor = new Ext.Panel({
            layout: "border",
            items: [new Ext.Panel({
                autoScroll: true,
                items: [this.grid],
                region: "center",
                layout: "fit",
                bbar: this.pagingtoolbar
            })]
        });

        this.layout.removeAll();
        this.layout.add(this.editor);
        this.layout.doLayout();

    },


    getGridConfig: function($super) {
        var config = $super();
        config.onlyDirectChildren = this.onlyDirectChildren;
        config.pageSize = this.pagingtoolbar.pageSize;
        return config;
    },


    onRowContextmenu: function (grid, rowIndex, event) {

        $(grid.getView().getRow(rowIndex)).animate( { backgroundColor: '#E0EAEE' }, 100).animate( {
            backgroundColor: '#fff' }, 400);

        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);
        var selectedRows = grid.getSelectionModel().getSelections();

        if (selectedRows.length <= 1) {

            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: function (data) {
                    pimcore.helpers.openObject(data.data.id, "object");
                }.bind(this, data)
            }));
            menu.add(new Ext.menu.Item({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_show_in_tree",
                handler: function (data) {
                    try {
                        try {
                            Ext.getCmp("pimcore_panel_tree_objects").expand();
                            var tree = pimcore.globalmanager.get("layout_object_tree");
                            pimcore.helpers.selectPathInTree(tree.tree, data.data.idPath);
                        } catch (e) {
                            console.log(e);
                        }

                    } catch (e2) { console.log(e2); }
                }.bind(grid, data)
            }));
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (data) {
                    var store = this.getStore();
                    pimcore.helpers.deleteObject(data.data.id, store.reload.bind(this.getStore()));
                }.bind(grid, data)
            }));
        } else {
            menu.add(new Ext.menu.Item({
                text: t('open_selected'),
                iconCls: "pimcore_icon_open",
                handler: function (data) {
                    var selectedRows = grid.getSelectionModel().getSelections();
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
                    var selectedRows = grid.getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; i++) {
                        ids.push(selectedRows[i].data.id);
                    }
                    ids = ids.join(',');

                    pimcore.helpers.deleteObject(ids, function() {
                        this.getStore().reload();
                        pimcore.globalmanager.get("layout_object_tree").tree.getRootNode().reload();
                    }.bind(this)
                    );
                }.bind(grid, data)
            }));
        }

        event.stopEvent();
        menu.showAt(event.getXY());
    }




});
