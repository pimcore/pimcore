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

pimcore.registerNS("pimcore.element.selector.object");
pimcore.element.selector.object = Class.create(pimcore.element.selector.abstract, {

    fieldObject: {},
    initStore: function () {
        return 0; // dummy
    },
    
    getForm: function () {
        
        var compositeConfig = {
            xtype: "compositefield",
            hideLabel: true,
            items: [{
                xtype: "textfield",
                name: "query",
                width: 340,
                hideLabel: true,
                enableKeyEvents: true,
                listeners: {
                    "keydown" : function (field, key) {
                        if (key.getKey() == key.ENTER) {
                            this.search();
                        }
                    }.bind(this)
                }
            }, new Ext.Button({
                handler: function () {
                    window.open("http://dev.mysql.com/doc/refman/5.6/en/fulltext-boolean.html");
                },
                iconCls: "pimcore_icon_menu_help"
            })]
        };
        
        // check for restrictions
        var possibleRestrictions = ["folder", "object", "variant"];
        var filterStore = [];
        var selectedStore = [];
        for (var i=0; i<possibleRestrictions.length; i++) {
           if(this.parent.restrictions.subtype.object && in_array(possibleRestrictions[i], this.parent.restrictions.subtype.object )) {
                filterStore.push([possibleRestrictions[i], t(possibleRestrictions[i])]);
                selectedStore.push(possibleRestrictions[i]);
           }
        }
        
        // add all to store if empty
        if(filterStore.length < 1) {
            for (var i=0; i<possibleRestrictions.length; i++) {
                filterStore.push([possibleRestrictions[i], t(possibleRestrictions[i])]);
                selectedStore.push(possibleRestrictions[i]);
            }
        }
        
        var selectedValue = selectedStore.join(",");
        if(filterStore.length > 1) {
            filterStore.splice(0,0,[selectedValue, t("all_types")]);
        }
        
        if(!this.parent.initialRestrictions.specific || (!this.parent.initialRestrictions.specific.classes || this.parent.initialRestrictions.specific.classes.length < 1)) {
            // only add the subtype filter if there is no class restriction
            compositeConfig.items.push({
                xtype: "combo",
                store: filterStore,
                mode: "local",
                name: "subtype",
                triggerAction: "all",
                forceSelection: true,
                value: selectedValue
            });
        }

        
        // classes
        var possibleClassRestrictions = [];
        var classStore = pimcore.globalmanager.get("object_types_store");
        classStore.each(function (rec) {
             possibleClassRestrictions.push(rec.data.text);
        });
        
        var filterClassStore = [];
        var selectedClassStore = [];
        for (var i=0; i<possibleClassRestrictions.length; i++) {
           if(in_array(possibleClassRestrictions[i], this.parent.restrictions.specific.classes )) {
                filterClassStore.push([possibleClassRestrictions[i], ts(possibleClassRestrictions[i])]);
                selectedClassStore.push(possibleClassRestrictions[i]);
           }
        }
        
        // add all to store if empty
        if(filterClassStore.length < 1) {
            for (var i=0; i<possibleClassRestrictions.length; i++) {
                filterClassStore.push([possibleClassRestrictions[i], possibleClassRestrictions[i]]);
                selectedClassStore.push(possibleClassRestrictions[i]);
            }
        }
        
        var selectedClassValue = selectedClassStore.join(",");
        if(filterClassStore.length > 1) {
            filterClassStore.splice(0,0,[selectedClassValue, t("all_types")]);
        }
            
        this.classChangeCombo = new Ext.form.ComboBox({
            xtype: "combo",
            store: filterClassStore,
            mode: "local",
            name: "class",
            triggerAction: "all",
            forceSelection: true,
            value: selectedClassValue,
            listeners: {
                select: this.changeClass.bind(this)
            }
        });
        
        compositeConfig.items.push(this.classChangeCombo);
    
        
        // add button
        compositeConfig.items.push({
            xtype: "button",
            iconCls: "pimcore_icon_search",
            text: t("search"),
            handler: this.search.bind(this)
        });
        
        if(!this.formPanel) {
            this.formPanel = new Ext.form.FormPanel({
                layout: "pimcoreform",
                region: "north",
                bodyStyle: "padding: 5px;",
                height: 35,
                items: [compositeConfig]
            });
        }
        
        return this.formPanel;
    },
    
    getSelectionPanel: function () {
        if(!this.selectionPanel) {
            
            this.selectionStore = new Ext.data.JsonStore({
                data: [],
                fields: ["id", "type", "filename", "fullpath", "subtype", {name:"classname",convert: function(v, rec){
                    return ts(rec.classname);
                }}]
            });
            
            this.selectionPanel = new Ext.grid.GridPanel({
               region: "east",
               title: t("your_selection"),
               tbar: [{
                    xtype: "tbtext",
                    text: t("double_click_to_add_item_to_selection"),
                    autoHeight: true,
                    width: 180,
                    style: {
                        whiteSpace: "normal"
                    }
               }],
               tbarCfg: {
                    autoHeight: true
               },
               width: 200,
               store: this.selectionStore,
               columns: [
                    {header: t("type"), width: 25, sortable: true, dataIndex: 'subtype'},
                    {header: t("filename"), width: 40, sortable: true, dataIndex: 'filename'}
                ],
                viewConfig: {
                    forceFit: true
                },
                listeners: {
                    rowcontextmenu: function (grid, rowIndex, event) {
                        var menu = new Ext.menu.Menu();
                        var data = grid.getStore().getAt(rowIndex);
                
                        menu.add(new Ext.menu.Item({
                            text: t('remove'),
                            iconCls: "pimcore_icon_delete",
                            handler: function (index, item) {
                                this.selectionStore.removeAt(index);
                                item.parentMenu.destroy();
                            }.bind(this, rowIndex)
                        }));
                
                        event.stopEvent();
                        menu.showAt(event.getXY());
                    }.bind(this)
                },
                sm: new Ext.grid.RowSelectionModel({singleSelect:true})
            });
        }
        
        return this.selectionPanel;
    },
    
    getResultPanel: function () {
        if (!this.resultPanel) {
            this.resultPanel = new Ext.Panel({
                region: "center",
                layout: "fit"
            });
            
            this.resultPanel.on("afterrender", this.changeClass.bind(this));
        }
        
        return this.resultPanel;
    },
    
    
    changeClass: function () {
        
        var selectedClass = this.classChangeCombo.getValue();
        
        if(selectedClass.indexOf(",") > 0) { // multiple classes because of a comma in the string
            // init default store
            this.initDefaultStore();
        } else {
            // get class definition
            Ext.Ajax.request({
                url: "/admin/object-helper/grid-get-column-config",
                params: {name: selectedClass},
                success: this.initClassStore.bind(this, selectedClass)
            });
        }
    },
    
    initClassStore: function (selectedClass, response) {
        var fields = [];
        if(response.responseText) {
            response = Ext.decode(response.responseText);
            fields = response.availableFields;
            this.gridLanguage = response.language;
            this.sortinfo = response.sortinfo;
        } else {
            fields = response;
        }
        
        var gridHelper = new pimcore.object.helpers.grid(selectedClass, fields, "/admin/search/search/find");
        this.store = gridHelper.getStore();
        var gridColumns = gridHelper.getGridColumns();
        var gridfilters = gridHelper.getGridFilters();

        this.fieldObject = {};
        for(var i = 0; i < fields.length; i++) {
            this.fieldObject[fields[i].key] = fields[i];
        }
        
        this.getGridPanel(gridColumns, gridfilters, selectedClass);
    },
    
    initDefaultStore: function () {
        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            root: "data",
            remoteSort: true,
            proxy : new Ext.data.HttpProxy({
                method: 'POST',
                url: "/admin/search/search/find"
            }),
            fields: ["id","fullpath","type","subtype","filename",{name:"classname",convert: function(v, rec){
                    return ts(rec.classname);
                }},"published"]
        });
        
        var columns = [
            {header: t("type"), width: 40, sortable: true, dataIndex: 'subtype', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                return '<div style="height: 16px;" class="pimcore_icon_asset  pimcore_icon_' + value + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
            }},
            {header: 'ID', width: 40, sortable: true, dataIndex: 'id', hidden: true},
            {header: t("published"), width: 40, sortable: true, dataIndex: 'published', hidden: true},
            {header: t("path"), width: 200, sortable: true, dataIndex: 'fullpath'},
            {header: t("filename"), width: 200, sortable: true, dataIndex: 'filename', hidden: true},
            {header: t("class"), width: 200, sortable: true, dataIndex: 'classname'}
        ];
        
        // filter dummy
        var gridfilters = new Ext.ux.grid.GridFilters({
            encode: true,
            local: false,
            filters: []
        });
        
        this.getGridPanel(columns, gridfilters);
    },
    
    getGridPanel: function (columns, gridfilters, selectedClass) {
        
        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_objects_found")
        });

        this.gridPanel = new Ext.grid.GridPanel({
            store: this.store,
            border: false,
            columns: columns,
            loadMask: true,
            columnLines: true,
            plugins: [gridfilters],
            stripeRows: true,
            viewConfig: {
                forceFit: false
            },
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            bbar: this.pagingtoolbar,
            listeners: {
                rowdblclick: function (grid, rowIndex, ev) {
                    
                    var data = grid.getStore().getAt(rowIndex);
                                            
                    if(this.parent.multiselect) {
                        this.addToSelection(data.data);
                    } else {
                        // select and close
                        this.parent.commitData(this.getData());
                    }
                }.bind(this)
            }
        });

        this.gridPanel.on("afterrender", function (grid) {
            if(selectedClass) {

                var classStore = pimcore.globalmanager.get("object_types_store");
                var classId = null;
                classStore.each(function (rec) {
                     if(rec.data.text == selectedClass) {
                         classId = rec.data.id;
                     }
                });

                var columnConfig = new Ext.menu.Item({
                    text: t("grid_column_config"),
                    iconCls: "pimcore_icon_grid_column_config",
                    handler: this.openColumnConfig.bind(this, selectedClass, classId)
                });
                grid.getView().hmenu.add(columnConfig);
            }
        }.bind(this));
        
        this.resultPanel.removeAll();
        this.resultPanel.add(this.gridPanel);
        this.resultPanel.doLayout();
    },

    openColumnConfig: function(selectedClass, classId) {
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
            classid: classId,
            selectedGridColumns: visibleColumns
        };
        var dialog = new pimcore.object.helpers.gridConfigDialog(columnConfig, function(data) {
            this.gridLanguage = data.language;
            this.initClassStore(selectedClass, data.columns);
        }.bind(this) );
    },

    getGridConfig : function () {
        var config = {
            language: this.gridLanguage,
            sortinfo: this.sortinfo,
            columns: {}
        };
        var cm = this.gridPanel.getColumnModel();
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
    },
    
    getGrid: function () {
        return this.gridPanel;
    },
    
    search: function () {
        var formValues = this.formPanel.getForm().getFieldValues();

        this.store.baseparams = {};
        this.store.setBaseParam("type", "object");
        this.store.setBaseParam("query", formValues.query);
        this.store.setBaseParam("subtype", formValues.subtype);
        this.store.setBaseParam("class", formValues["class"]);
        //this.store.load();

        this.pagingtoolbar.moveFirst();
    }
});