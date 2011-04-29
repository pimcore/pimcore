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
                title: t('search_edit'),
                border: false,
                layout: "fit",
                iconCls: "pimcore_icon_tab_search",
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
            params: {id: classId, objectId: this.object.id},
            success: this.setEditableGrid.bind(this)
        });
    },

    setEditableGrid: function (response) {

        var itemsPerPage = 20;
        var validFieldTypes = ["textarea","input","checkbox","select","numeric","wysiwyg","image","geopoint","country","href","multihref","objects","language","table","date","datetime","time","link","multiselect","password","slider","user"];
        var editableFieldTypes = ["textarea","input","checkbox","select","numeric","wysiwyg","country","language","user"]

        var fields = Ext.decode(response.responseText);
        var editor;
        var plugins = [];
        var cm;
        var gridColumns = [];
        var store;
        var editorConfig;
        var columnWidth;
        
        // get current class
        var classStore = pimcore.globalmanager.get("object_types_store");
        var klass = classStore.getById(this.classId);
        var propertyVisibility = klass.get("propertyVisibility");
        
        // the store
        var readerFields = [];
        readerFields.push({name: "id", allowBlank: true});
        readerFields.push({name: "fullpath", allowBlank: true});
        readerFields.push({name: "published", allowBlank: true});
        readerFields.push({name: "creationDate", allowBlank: true});
        readerFields.push({name: "modificationDate", allowBlank: true});
        readerFields.push({name: "inheritedFields", allowBlank: false});
        for (var i = 0; i < fields.length; i++) {
            readerFields.push({name: fields[i].key, allowBlank: true});
        }
        
        var proxy = new Ext.data.HttpProxy({
            url: '/admin/object/grid-proxy/classId/' + this.classId + "/folderId/" + this.object.id,
            method: 'post'
        });
        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data'
        }, readerFields);
        var writer = new Ext.data.JsonWriter();

        this.store = new Ext.data.Store({
            restful: false,
            idProperty: 'id',
            remoteSort: true,
            proxy: proxy,
            reader: reader,
            writer: writer,
            baseParams: {
                limit: itemsPerPage
            },
            listeners: {
                write : function(store, action, result, response, rs) {
                },
                exception: function (conn, mode, action, request, response, store) {
                    if(action == "update") {
                        Ext.MessageBox.alert(t('error'), t('cannot_save_object_please_try_to_edit_the_object_in_detail_view'));
                        this.store.rejectChanges();
                    }
                }.bind(this)
            }
        });
        this.store.load();
        
        // configure grid columns
        var selectionColumn = new Ext.grid.CheckboxSelectionModel();
        gridColumns.push(selectionColumn);
        
        gridColumns.push({header: "ID (System)", width: 60, sortable: true, dataIndex: "id", editable: false, hidden: !propertyVisibility.grid.id});
        gridColumns.push({header: t("path") + " (System)", width: 200, sortable: true, dataIndex: "fullpath", editable: false, hidden: !propertyVisibility.grid.path});
        cm = new Ext.grid.CheckColumn({
            header: t("published") + " (System)",
            dataIndex: "published",
            sortable:true,
            hidden: !propertyVisibility.grid.published
        });
        gridColumns.push(cm);
        plugins.push(cm);
        gridColumns.push({header: t("creationdate") + " (System)", width: 200, sortable: true, dataIndex: "creationDate", editable: false, renderer: function(d) {
            var date = new Date(d * 1000);
            return date.format("Y-m-d H:i:s");
        }, hidden: !propertyVisibility.grid.creationDate});
        gridColumns.push({header: t("modificationdate") + " (System)", width: 200, sortable: true, dataIndex: "modificationDate", editable: false, renderer: function(d) {
            var date = new Date(d * 1000);
            return date.format("Y-m-d H:i:s");
        }, hidden: !propertyVisibility.grid.modificationDate});

        for (var i = 0; i < fields.length; i++) {
            if (in_array(fields[i].type, validFieldTypes)) {

                editor = null;
                cm = null;
                store = null;
                editorConfig = {};
                columnWidth = null;

                if (fields[i].config) {
                    if (fields[i].config.width) {
                        if (parseInt(fields[i].config.width) > 10) {
                            editorConfig.width = fields[i].config.width;
                            columnWidth = fields[i].config.width;
                        }
                    }
                }
                
                if(fields[i].layout.noteditable && in_array(fields[i].type,editableFieldTypes)) {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, editable: false, hidden: !fields[i].visibleGridView});
                    editor = null;
                    continue;
                }
                

                // INPUT
                if (fields[i].type == "input") {
                    editor = new Ext.form.TextField(editorConfig);
                }
                // NUMERIC
                else if (fields[i].type == "numeric") {
                    editorConfig.decimalPrecision = 20;
                    editor = new Ext.ux.form.SpinnerField(editorConfig);
                }
                // TEXTAREA
                else if (fields[i].type == "textarea") {
                    editor = new Ext.form.TextArea(editorConfig);
                }
                // DATE
                else if (fields[i].type == "date") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, editable: false, renderer: function (record) {
                        if (record) {
                            var timestamp = intval(record) * 1000;
                            var date = new Date(timestamp);

                            return date.format("Y-m-d");
                        }
                        return "";
                    }});
                    editor = null;
                }
                // DATETIME
                else if (fields[i].type == "datetime") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, editable: false, renderer: function (record) {
                        if (record) {
                            var timestamp = intval(record) * 1000;
                            var date = new Date(timestamp);

                            return date.format("Y-m-d H:i");
                        }
                        return "";
                    }});
                    editor = null;
                }
                // TIME
                else if (fields[i].type == "time") {
                    gridColumns.push({header: ts(fields[i].label), width: 100, sortable: false, dataIndex: fields[i].key, editable: false});
                    editor = null;
                }
                // SELECT & COUNTRY & LANGUAGE & USER
                else if (fields[i].type == "select" || fields[i].type == "country" || fields[i].type == "language" || fields[i].type == "user") {

                    store = new Ext.data.JsonStore({
                        autoDestroy: true,
                        root: 'store',
                        fields: ['key',"value"],
                        data: fields[i].config
                    });

                    editorConfig = Object.extend(editorConfig, {
                        store: store, 
                        triggerAction: "all",
                        editable: false,
                        mode: "local",
                        valueField: 'value',
                        displayField: 'key'
                    });

                    editor = new Ext.form.ComboBox(editorConfig);
                }
                // CHECKBOX
                else if (fields[i].type == "checkbox") {
                    cm = new Ext.grid.CheckColumn({
                        header: ts(fields[i].label),
                        dataIndex: fields[i].key,
                        renderer: function (key, value, metaData, record, rowIndex, colIndex, store) {
                            if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                                metaData.css += " grid_value_inherited";
                            }
                            metaData.css += ' x-grid3-check-col-td';
                            return String.format('<div class="x-grid3-check-col{0}">&#160;</div>', value ? '-on' : '');
                        }.bind(this, fields[i].key)
                    });
                    gridColumns.push(cm);
                    plugins.push(cm);
                }
                // WYSIWYG
                else if (fields[i].type == "wysiwyg") {
                    editor = new Ext.form.HtmlEditor({
                        width: 500,
                        height: 300
                    });
                    gridColumns.push({header: ts(fields[i].label), width: 500, sortable: true, dataIndex: fields[i].key, editor: editor, renderer: function (key, value, metaData, record, rowIndex, colIndex, store) {
                            if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                                metaData.css += " grid_value_inherited";
                            }
                            return value;
                        }.bind(this, fields[i].key)
                    });
                    editor = null;
                }
                // IMAGE 
                else if (fields[i].type == "image") {
                    gridColumns.push({header: ts(fields[i].label), width: 100, sortable: false, dataIndex: fields[i].key, editable: false, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }

                        if (value && value.id) {
                            return '<img src="/admin/asset/get-image-thumbnail/id/' + value.id + '/width/88/aspectratio/true" />';
                        }
                    }.bind(this, fields[i].key)});
                    editor = null;
                }
                // GEOPOINT
                else if (fields[i].type == "geopoint") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, editable: false, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }

                        if (value) {
                            if (value.latitude && value.longitude) {

                                var width = 140;
                                var mapZoom = 10;
                                var mapUrl = "http://dev.openstreetmap.org/~pafciu17/?module=map&center=" + value.longitude + "," + value.latitude + "&zoom=" + mapZoom + "&type=mapnik&width=" + width + "&height=x80&points=" + value.longitude + "," + value.latitude + ",pointImagePattern:red";
                                if (pimcore.settings.google_maps_api_key) {
                                    mapUrl = "http://maps.google.com/staticmap?center=" + value.latitude + "," + value.longitude + "&zoom=" + mapZoom + "&size=" + width + "x80&markers=" + value.latitude + "," + value.longitude + ",red&sensor=false&key=" + pimcore.settings.google_maps_api_key;
                                }

                                return '<img src="' + mapUrl + '" />';
                            }
                        }
                    }.bind(this, fields[i].key)});
                    editor = null;
                }
                // HREF
                else if (fields[i].type == "href") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, editable: false, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }
                        return value;
                    }.bind(this, fields[i].key)});
                    editor = null;
                }
                // MULTIHREF & OBJECTS
                else if (fields[i].type == "multihref" || fields[i].type == "objects") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, editable: false, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }

                        if (value.length > 0) {
                            return value.join("<br />");
                        }
                    }.bind(this, fields[i].key)});
                    editor = null;
                }
                // SLIDER
                else if (fields[i].type == "slider") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, editable: false, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }
                        return value;
                    }.bind(this, fields[i].key)});
                    editor = null;
                }
                // PASSWORD
                else if (fields[i].type == "password") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, editable: false, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }

                        return "**********";
                    }.bind(this, fields[i].key)});
                    editor = null;
                }
                // LINK
                else if (fields[i].type == "link") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, editable: false, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }
                        return value;
                    }.bind(this, fields[i].key)});
                    editor = null;
                }
                // MULTISELECT
                else if (fields[i].type == "multiselect") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, editable: false, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }
                        if (value!=null && value.length > 0) {
                            return value.join(",");
                        }
                    }.bind(this, fields[i].key)});
                    editor = null;
                }
                // TABLE
                else if (fields[i].type == "table") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, editable: false, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }

                        if (value && value.length > 0) {
                            var table = '<table cellpadding="2" cellspacing="0" border="1">';
                            for (var i = 0; i < value.length; i++) {
                                table += '<tr>';
                                for (var c = 0; c < value[i].length; c++) {
                                    table += '<td>' + value[i][c] + '</td>';
                                }
                                table += '</tr>';
                            }
                            table += '</table>';
                            return table;
                        }
                        return "";
                    }.bind(this, fields[i].key)});
                    editor = null;
                }

                // add column
                if (editor) {
                    gridColumns.push({header: ts(fields[i].label), sortable: true, dataIndex: fields[i].key, editor: editor, renderer: function (key, value, metaData, record, rowIndex, colIndex, store) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }
                        return value;
                    }.bind(this, fields[i].key)
                    });
                }
                
                // is visible or not   
                gridColumns[gridColumns.length-1].hidden = !fields[i].visibleGridView;
                gridColumns[gridColumns.length-1].layout = fields[i];
            }
        }
        
        // add filters
        var selectFilterFields;
        var configuredFilters = [{
            type: "date",
            dataIndex: "creationDate"
        },{
            type: "date",
            dataIndex: "modificationDate"
        }];
        
        for (var i = 0; i < fields.length; i++) {
            if (in_array(fields[i].type, validFieldTypes)) {
                store = null;
                selectFilterFields = null;
                
                if (fields[i].type == "input" || fields[i].type == "textarea" || fields[i].type == "wysiwyg" || fields[i].type == "time") {
                    configuredFilters.push({
                        type: 'string',
                        dataIndex: fields[i].key
                    });
                } else if (fields[i].type == "numeric" || fields[i].type == "slider") {
                    configuredFilters.push({
                        type: 'numeric',
                        dataIndex: fields[i].key
                    });
                } else if (fields[i].type == "date" || fields[i].type == "datetime") {     
                    configuredFilters.push({
                        type: 'date',
                        dataIndex: fields[i].key
                    });
                } else if (fields[i].type == "select" || fields[i].type == "country" || fields[i].type == "language") {
                    selectFilterFields = [];
                    
                    store = new Ext.data.JsonStore({
                        autoDestroy: true,
                        root: 'store',
                        fields: ['key',"value"],
                        data: fields[i].config
                    });
                    
                    store.each(function (rec) {
                        selectFilterFields.push(rec.data.value);                                                               
                    });                   
                                                            
                    configuredFilters.push({
                        type: 'list',
                        dataIndex: fields[i].key,
                        options: selectFilterFields
                    });
                } else if (fields[i].type == "checkbox") {
                    configuredFilters.push({
                        type: 'boolean',
                        dataIndex: fields[i].key
                    });
                } else if (fields[i].type == "multiselect") {
                    selectFilterFields = [];
                    
                    store = new Ext.data.JsonStore({
                        autoDestroy: true,
                        root: 'options',
                        fields: ['key',"value"],
                        data: fields[i].layout
                    });
                    
                    store.each(function (rec) {
                        selectFilterFields.push(rec.data.value);                                                               
                    });                   
                                                            
                    configuredFilters.push({
                        type: 'list',
                        dataIndex: fields[i].key,
                        options: selectFilterFields
                    });
                }
            }
        }
        
        // filters
        this.gridfilters = new Ext.ux.grid.GridFilters({
            encode: true,
            local: false,
            filters: configuredFilters
        });
        plugins.push(this.gridfilters);
        
        
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
            sm: selectionColumn,
            trackMouseOver: true,
            loadMask: true,
            viewConfig: {
                forceFit: false
            },
            tbar: [this.toolbarFilterInfo
            ,"->"
            ,this.sqlEditor
            ,this.sqlButton,{
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
            }]
        });
        this.grid.on("rowcontextmenu", this.onRowContextmenu);

        this.grid.on("afterrender", function (grid) {
            grid.getView().hmenu.add({
                text: t("batch_change"),
                iconCls: "pimcore_icon_batch",
                handler: function (view) {
                    this.batchPrepare(view.hdCtxIndex);
                }.bind(this, grid.getView())
            });
            grid.getView().hmenu.add({
                text: t("batch_change_selected"),
                iconCls: "pimcore_icon_batch",
                handler: function (view) {
                    this.batchPrepare(view.hdCtxIndex, true);
                }.bind(this, grid.getView())
            });
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

    batchPrepare: function(columnIndex, onlySelected){

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
        // no batch for system properties
        if (columnIndex < 5) {
            return;
        }

        var fieldInfo = this.grid.getColumnModel().config[columnIndex+1];
        if(!fieldInfo.layout || !fieldInfo.layout.layout) {
            return;
        }

        if(fieldInfo.layout.layout.noteditable) {
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
            columns: {}
        };
        var cm = this.grid.getColumnModel();
        for (var i=0; i<cm.config.length; i++) {
            if(cm.config[i].dataIndex) {
                config.columns[cm.config[i].dataIndex] = {
                    name: cm.config[i].dataIndex,
                    position: i,
                    hidden: cm.config[i].hidden
                };
            }
        }
        
        return config;        
    }
});
