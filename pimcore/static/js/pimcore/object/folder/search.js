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
pimcore.object.search = Class.create({
    systemColumns: ["id", "fullpath", "published", "type", "subtype", "filename", "classname", "creationDate", "modificationDate"],
    fieldObject: {},

    title: t('search_edit'),
    icon: "pimcore_icon_tab_search",
    onlyDirectChildren: false,

    sortInfo: {},
    initialize: function(object) {
        this.object = object;
        this.currentClass;
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
            url: "/admin/object/grid-get-column-config",
            params: {id: classId, objectId: this.object.id, gridtype: "grid"},
            success: this.setEditableGrid.bind(this)
        });
    },

    setEditableGrid: function (response) {

        var itemsPerPage = 20;

        var fields = [];
        if(response.responseText) {
            response = Ext.decode(response.responseText);
            fields = response.availableFields;
            this.gridLanguage = response.language;
            this.sortinfo = response.sortinfo;
        } else {
            fields = response;
        }

        this.fieldObject = {};
        for(var i = 0; i < fields.length; i++) {
            this.fieldObject[fields[i].key] = fields[i];
        }

//        console.log(this.fieldObject);

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
        
        this.sqlEditor = new Ext.form.TextField({
            xtype: "textfield",
            width: 500,
            name: "condition",
            hidden: true,
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        this.gridfilters.clearFilters();
                        
                        this.store.baseparams = {};
                        this.store.setBaseParam("condition", field.getValue());
                        
                        this.pagingtoolbar.moveFirst();                        
                    }
                }.bind(this)
            }
        });

        this.checkboxOnlyDirectChildren = new Ext.form.Checkbox({
            name: "onlyDirectChildren",
            style: "margin-bottom: 5px; margin-left: 5px",
            checked: this.onlyDirectChildren,
            listeners: {
                "check" : function (field, checked) {
                    this.gridfilters.clearFilters();

                    this.store.baseparams = {};
                    this.store.setBaseParam("only_direct_children", checked);

                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        });
        
        this.sqlButton = new Ext.Button({
            iconCls: "pimcore_icon_sql",
            enableToggle: true,
            tooltip: t("direct_sql_query"),
            handler: function (button) {
                
                this.gridfilters.clearFilters();
                this.sqlEditor.setValue("");
                
                // reset base params, because of the condition
                this.store.baseparams = {};
                this.store.setBaseParam("condition", null);
                this.pagingtoolbar.moveFirst();
                                    
                if(button.pressed) {
                    this.sqlEditor.show();
                } else {
                    this.sqlEditor.hide();
                }
            }.bind(this)
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
            var columnConfig = new Ext.menu.Item({
                text: t("grid_column_config"),
                iconCls: "pimcore_icon_grid_column_config",
                handler: this.openColumnConfig.bind(this)
            });
            grid.getView().hmenu.add(columnConfig);
            
            var batchAllMenu = new Ext.menu.Item({
                text: t("batch_change"),
                iconCls: "pimcore_icon_batch",
                handler: function (view) {
                    this.batchPrepare(view.hdCtxIndex);
                }.bind(this, grid.getView())
            });
            grid.getView().hmenu.add(batchAllMenu);

            var batchSelectedMenu = new Ext.menu.Item({
                text: t("batch_change_selected"),
                iconCls: "pimcore_icon_batch",
                handler: function (view) {
                    this.batchPrepare(view.hdCtxIndex, true);
                }.bind(this, grid.getView())
            });
            grid.getView().hmenu.add(batchSelectedMenu);

            grid.getView().hmenu.on('beforeshow', function (batchAllMenu, batchSelectedMenu, view) {
                // no batch for system properties
                if(this.systemColumns.indexOf(view.cm.config[view.hdCtxIndex].dataIndex) > 0) {
                    batchAllMenu.hide();
                    batchSelectedMenu.hide();
                } else {
                    batchAllMenu.show();
                    batchSelectedMenu.show();
                }
                
            }.bind(this, batchAllMenu, batchSelectedMenu, grid.getView()));
        }.bind(this));

        this.grid.on("sortchange", function(grid, sortinfo) {
            this.sortinfo = sortinfo;
        }.bind(this));
        
        // check for filter updates
        this.grid.on("filterupdate", function () {
            var filterString = "";
            var filterStringConfig = [];
            var filterData = this.gridfilters.getFilterData();
            var operator;
            
            // reset
            this.toolbarFilterInfo.setText(" ");
            
            if(filterData.length > 0) {
                
                for (var i=0; i<filterData.length; i++) {
                    
                    operator = "=";
                    if (filterData[i].data.type == "string") {
                        operator = "LIKE";
                    } else if (filterData[i].data.type == "numeric" || filterData[i].data.type == "date") {
                        if(filterData[i].data.comparison == "lt") {
                            operator = "&lt;";
                        } else if(filterData[i].data.comparison == "gt") {
                            operator = "&gt;";
                        }
                    } else if (filterData[i].data.type == "boolean") {
                       filterData[i].value = filterData[i].data.value ? "true" : "false";
                    }
                    
                    if(filterData[i].data.value && typeof filterData[i].data.value == "object") {
                        filterStringConfig.push(filterData[i].field + " " + operator + " (" + filterData[i].data.value.join(" OR ") + ")");
                    } else {
                        filterStringConfig.push(filterData[i].field + " " + operator + " " + filterData[i].data.value);
                    }
                }
                
                this.toolbarFilterInfo.setText("<b>" + t("filter_condition") + ": " + filterStringConfig.join(" AND ") + "</b>");
            }
        }.bind(this))
        
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


    openColumnConfig: function() {
        var fields = this.getGridConfig().columns;

        var fieldKeys = Object.keys(fields);

        var visibleColumns = [];
        for(var i = 0; i < fieldKeys.length; i++) {
            if(!fields[fieldKeys[i]].hidden) {
                visibleColumns.push({
                    key: fieldKeys[i],
                    label: fields[fieldKeys[i]].fieldConfig.label,
                    dataType: fields[fieldKeys[i]].fieldConfig.type,
                    layout: fields[fieldKeys[i]].fieldConfig.layout
                });
            }
        }

        var columnConfig = {
            language: this.gridLanguage,
            classid: this.classId,
            selectedGridColumns: visibleColumns
        };
        var dialog = new pimcore.object.helpers.gridConfigDialog(columnConfig, function(data) {
            this.gridLanguage = data.language;
            this.setEditableGrid(data.columns);
        }.bind(this) );
    },


    batchPrepare: function(columnIndex, onlySelected){
        // no batch for system properties
        if(this.systemColumns.indexOf(this.grid.getColumnModel().config[columnIndex].dataIndex) > 0) {
            return;
        }
//        if (columnIndex <= 8) {
//            return;
//        }

        var jobs = [];
        if(onlySelected) {
            var selectedRows = this.grid.getSelectionModel().getSelections();
            for (var i=0; i<selectedRows.length; i++) {
                jobs.push(selectedRows[i].get("id"));
            }
            this.batchOpen(columnIndex,jobs);
        } else {

            var filters = "";
        var condition = "";


        if(this.sqlButton.pressed) {
            condition = this.sqlEditor.getValue();
        } else {
            var filterData = this.gridfilters.getFilterData();
            if(filterData.length > 0) {
                filters = this.gridfilters.buildQuery(filterData).filter;
            }
        }


       var params = {
            filter: filters,
            condition: condition,
            classId: this.classId,
            folderId: this.object.id
        };


            Ext.Ajax.request({
            url: "/admin/object/get-batch-jobs",
            params: params,
            success: function (columnIndex,response) {
                var rdata = Ext.decode(response.responseText);
                if (rdata.success && rdata.jobs) {
                    this.batchOpen(columnIndex, rdata.jobs);
                }

            }.bind(this,columnIndex) 
        });
        }

    },

    batchOpen: function (columnIndex, jobs) {

        columnIndex = columnIndex-1;

        var fieldInfo = this.grid.getColumnModel().config[columnIndex+1];
        if(!fieldInfo.layout || !fieldInfo.layout.layout) {
            return;
        }

        if(fieldInfo.layout.layout.noteditable) {
            Ext.MessageBox.alert(t('error'), t('this_element_cannot_be_edited'));
            return;
        }

        var editor = new pimcore.object.tags[fieldInfo.layout.type](null, fieldInfo.layout.layout);
        this.batchWin = new Ext.Window({
            title: t("batch_edit_field") + " " + fieldInfo.header,
            items: [
                {
                    xtype: "form",
                    border: false,
                    items: [editor.getLayoutEdit()],
                    bodyStyle: "padding: 10px;",
                    buttons: [
                        {
                            text: t("save"),
                            handler: this.batchProcess.bind(this, jobs, editor, fieldInfo, true)
                        }
                    ]
                }
            ],
            bodyStyle: "background: #fff;",
            width: 700
        });
        this.batchWin.show();

    },

    batchProcess: function (jobs,  editor, fieldInfo, initial) {

        if(initial){

            this.batchErrors = [];
            this.batchJobCurrent = 0;

            var newValue = editor.getValue();

            var valueType = "primitive";
            if (newValue && typeof newValue == "object") {
                newValue = Ext.encode(newValue);
                valueType = "object";
            }

            this.batchParameters = {
                name: fieldInfo.dataIndex,
                value: newValue,
                valueType: valueType
            };


            this.batchWin.close();

            this.batchProgressBar = new Ext.ProgressBar({
                text: t('Initializing'),
                style: "margin: 10px;",
                width: 500
            });

            this.batchProgressWin = new Ext.Window({
                items: [this.batchProgressBar],
                modal: true,
                bodyStyle: "background: #fff;",
                closable: false
            });
            this.batchProgressWin.show();

        }

        if (this.batchJobCurrent >= jobs.length) {
            this.batchProgressWin.close();
            this.pagingtoolbar.moveFirst();

            // error handling
            if (this.batchErrors.length > 0) {

                var jobs = [];
                for (var i = 0; i < this.batchErrors.length; i++) {
                    jobs.push(this.batchErrors[i].job);
                }
                Ext.Msg.alert(t("error"), t("error_jobs") + ": " + jobs.join(","));
            }

            return;
        }

        var status = (this.batchJobCurrent / jobs.length);
        var percent = Math.ceil(status * 100);
        this.batchProgressBar.updateProgress(status, percent + "%");

        this.batchParameters.job = jobs[this.batchJobCurrent];
        Ext.Ajax.request({
            url: "/admin/object/batch",
            params: this.batchParameters,
            success: function (jobs, response) {
                
                var rdata = Ext.decode(response.responseText);
                if (rdata) {
                    if (!rdata.success) {
                        this.batchErrors.push({
                            job: response.request.parameters.job
                        });
                    }
                }
                else {
                    this.batchErrors.push({
                        job: response.request.parameters.job
                    });
                }

                window.setTimeout(function() {
                    this.batchJobCurrent++;
                    this.batchProcess(jobs);
                }.bind(this), 400);
            }.bind(this,jobs)
        });
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

        event.stopEvent();
        menu.showAt(event.getXY());
    },
    
    
    startCsvExport: function () {
        var values = [];
        var filters = "";
        var condition = "";
        
        if(this.sqlButton.pressed) {
            condition = this.sqlEditor.getValue();
        } else {
            var filterData = this.gridfilters.getFilterData();
            if(filterData.length > 0) {
                filters = this.gridfilters.buildQuery(filterData).filter;
            }
        }
        
        
        
        var path = "/admin/object/export/classId/" + this.classId + "/folderId/" + this.object.id ;          
        path = path + "/?" + Ext.urlEncode({
            filter: filters,
            condition: condition
        });

        location.href = path;
    },
    
    getGridConfig : function () {
        var config = {
            language: this.gridLanguage,
            sortinfo: this.sortinfo,
            columns: {}
        };
        var cm = this.grid.getColumnModel();
        for (var i=0; i<cm.config.length; i++) {
            if(cm.config[i].dataIndex) {
                config.columns[cm.config[i].dataIndex] = {
                    name: cm.config[i].dataIndex,
                    position: i,
                    hidden: cm.config[i].hidden,
                    fieldConfig: this.fieldObject[cm.config[i].dataIndex]
                };
            }
        }

        return config;
    }
});
