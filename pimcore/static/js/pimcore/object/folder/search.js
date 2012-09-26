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

            var classStore = pimcore.globalmanager.get("object_types_store");

            // check for classtypes inside of the folder if there is only one type don't display the selection
            var toolbarConfig;

            if (this.object.data.classes && typeof this.object.data.classes == "object") {

                if (this.object.data.classes.length < 1) {
                    return;
                }

                if (this.object.data.classes.length > 1) {
                    toolbarConfig = [new Ext.Toolbar.TextItem({
                        text: t("please_select_a_type")
                    }),new Ext.form.ComboBox({
                        name: "selectClass",
                        listWidth: 'auto',
                        store: classStore,
                        valueField: 'id',
                        displayField: 'translatedText',
                        triggerAction: 'all',
                        listeners: {
                            "select": this.changeClassSelect.bind(this)
                        }
                    })];
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
                {language: this.gridLanguage},
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
                text: t("add_childs"),
                iconCls: "pimcore_icon_add_child",
                handler: this.addChilds.bind(this)
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
            value: 20,
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
        return config;
    },


    onRowContextmenu: function (grid, rowIndex, event) {
        
        $(grid.getView().getRow(rowIndex)).animate( { backgroundColor: '#E0EAEE' }, 100).animate( { backgroundColor: '#fff' }, 400);
        
        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);

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

                } catch (e) { console.log(e); }
            }.bind(grid, data)
        }));
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: function (data) {
                pimcore.helpers.deleteObject(data.data.id, this.getStore().reload.bind(this.getStore()));
            }.bind(grid, data)
        }));

        event.stopEvent();
        menu.showAt(event.getXY());
    },

    addChilds: function () {
        pimcore.helpers.itemselector(true, function (selection) {

            var jobs = [];

            if(selection && selection.length > 0) {
                for(var i=0; i<selection.length; i++) {
                    jobs.push([{
                        url: "/admin/object/update",
                        params: {
                            id: selection[i]["id"],
                            values: Ext.encode({
                                parentId: this.object.id
                            })
                        }
                    }]);
                }
            }

            this.addChildProgressBar = new Ext.ProgressBar({
                text: t('initializing')
            });

            this.addChildWindow = new Ext.Window({
                layout:'fit',
                width:500,
                bodyStyle: "padding: 10px;",
                closable:false,
                plain: true,
                modal: true,
                items: [this.addChildProgressBar]
            });

            this.addChildWindow.show();

            var pj = new pimcore.tool.paralleljobs({
                success: function () {

                    if(this.addChildWindow) {
                        this.addChildWindow.close();
                    }

                    this.deleteProgressBar = null;
                    this.addChildWindow = null;

                    this.store.reload();

                    try {
                        var node = pimcore.globalmanager.get("layout_object_tree").tree.getNodeById(this.object.id);
                        node.reload();
                    } catch (e) {
                        // node is not present
                    }
                }.bind(this),
                update: function (currentStep, steps, percent) {
                    if(this.addChildProgressBar) {
                        var status = currentStep / steps;
                        this.addChildProgressBar.updateProgress(status, percent + "%");
                    }
                }.bind(this),
                failure: function (message) {
                    this.addChildWindow.close();
                    Ext.MessageBox.alert(t("error"), message);
                }.bind(this),
                jobs: jobs
            });

        }.bind(this), {
            type: ["object"],
            subtype: {
                object: ["object", "folder"]
            },
            specific: {
                classes: null
            }
        });
    }

});
