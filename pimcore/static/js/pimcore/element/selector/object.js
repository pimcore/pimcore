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
                width: 400,
                hideLabel: true,
                enableKeyEvents: true,
                listeners: {
                    "keydown" : function (field, key) {
                        if (key.getKey() == key.ENTER) {
                            this.search();
                        }
                    }.bind(this)
                }
            }]
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
                url: "/admin/object/grid-get-column-config",
                params: {name: selectedClass},
                success: this.initClassStore.bind(this, selectedClass)
            });
        }
    },
    
    initClassStore: function (selectedClass, response) {
        
//        var fields = Ext.decode(response.responseText);
//        var validFieldTypes = ["textarea","input","checkbox","select","numeric","wysiwyg","image","geopoint","country","href","multihref","objects","language","table","date","datetime","link","multiselect","password","slider","user"];
//
//        // the store
//        var readerFields = [];
//        readerFields.push({name: "id", allowBlank: true});
//        readerFields.push({name: "fullpath", allowBlank: true});
//        readerFields.push({name: "published", allowBlank: true});
//        readerFields.push({name: "type", allowBlank: true});
//        readerFields.push({name: "subtype", allowBlank: true});
//        readerFields.push({name: "filename", allowBlank: true});
//        readerFields.push({name: "classname", allowBlank: true});
//        readerFields.push({name: "creationDate", allowBlank: true});
//        readerFields.push({name: "modificationDate", allowBlank: true});
//        readerFields.push({name: "inheritedFields", allowBlank: false});
//
//        for (var i = 0; i < fields.length; i++) {
//            readerFields.push({name: fields[i].key, allowBlank: true});
//        }
//
//        var proxy = new Ext.data.HttpProxy({
//            url: "/admin/search/search/find"
//        });
//        var reader = new Ext.data.JsonReader({
//            totalProperty: 'total',
//            successProperty: 'success',
//            root: 'data'
//        }, readerFields);
//
//        this.store = new Ext.data.Store({
//            restful: false,
//            idProperty: 'id',
//            remoteSort: true,
//            proxy: proxy,
//            reader: reader,
//            baseParams: {
//                limit: 15,
//                "class": selectedClass
//            }
//        });
//
//        // get current class
//        var classStore = pimcore.globalmanager.get("object_types_store");
//        var klassIndex = classStore.findExact("text",selectedClass);
//        var klass = classStore.getAt(klassIndex);
//        var propertyVisibility = klass.get("propertyVisibility");
//
//        // init grid-columns
//        var gridColumns = [
//            {header: t("type"), width: 40, sortable: true, dataIndex: 'subtype', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
//                return '<div style="height: 16px;" class="pimcore_icon_asset  pimcore_icon_' + value + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
//            }},
//            {header: 'ID', width: 40, sortable: true, dataIndex: 'id', hidden: !propertyVisibility.search.id},
//            {header: t("published"), width: 40, sortable: true, dataIndex: 'published', hidden: !propertyVisibility.search.published},
//            {header: t("path"), width: 200, sortable: true, dataIndex: 'fullpath', hidden: !propertyVisibility.search.path},
//            {header: t("filename"), width: 200, sortable: true, dataIndex: 'filename', hidden: !propertyVisibility.search.path},
//            {header: t("class"), width: 200, sortable: true, dataIndex: 'classname',renderer: function(v){return ts(v);}, hidden: true},
//            {header: t("creationdate") + " (System)", width: 200, sortable: true, dataIndex: "creationDate", editable: false, renderer: function(d) {
//                var date = new Date(d * 1000);
//                return date.format("Y-m-d H:i:s");
//            }, hidden: !propertyVisibility.search.creationDate},
//            {header: t("modificationdate") + " (System)", width: 200, sortable: true, dataIndex: "modificationDate", editable: false, renderer: function(d) {
//                var date = new Date(d * 1000);
//                return date.format("Y-m-d H:i:s");
//            }, hidden: !propertyVisibility.search.modificationDate}
//        ];
//
//        for (var i = 0; i < fields.length; i++) {
//            if (in_array(fields[i].type, validFieldTypes)) {
//
//                cm = null;
//                store = null;
//
//                // DATE
//                if (fields[i].type == "date") {
//                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (record) {
//                        if (record) {
//                            var timestamp = intval(record) * 1000;
//                            var date = new Date(timestamp);
//
//                            return date.format("Y-m-d");
//                        }
//                        return "";
//                    }});
//                }
//                // DATETIME
//                else if (fields[i].type == "datetime") {
//                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (record) {
//                        if (record) {
//                            var timestamp = intval(record) * 1000;
//                            var date = new Date(timestamp);
//
//                            return date.format("Y-m-d H:i");
//                        }
//                        return "";
//                    }});
//                }
//                // IMAGE
//                else if (fields[i].type == "image") {
//                    gridColumns.push({header: ts(fields[i].label), width: 100, sortable: false, dataIndex: fields[i].key, renderer: function (record) {
//                        if (record && record.id) {
//                            return '<img src="/admin/asset/get-image-thumbnail/id/' + record.id + '/width/88/aspectratio/true" />';
//                        }
//                    }});
//                }
//                // GEOPOINT
//                else if (fields[i].type == "geopoint") {
//                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (record) {
//
//                        if (record) {
//                            if (record.latitude && record.longitude) {
//
//                                var width = 140;
//                                var mapZoom = 10;
//                                var mapUrl = "http://dev.openstreetmap.org/~pafciu17/?module=map&center=" + record.longitude + "," + record.latitude + "&zoom=" + mapZoom + "&type=mapnik&width=" + width + "&height=x80&points=" + record.longitude + "," + record.latitude + ",pointImagePattern:red";
//                                if (pimcore.settings.google_maps_api_key) {
//                                    mapUrl = "http://maps.google.com/staticmap?center=" + record.latitude + "," + record.longitude + "&zoom=" + mapZoom + "&size=" + width + "x80&markers=" + record.latitude + "," + record.longitude + ",red&sensor=false&key=" + pimcore.settings.google_maps_api_key;
//                                }
//
//                                return '<img src="' + mapUrl + '" />';
//                            }
//                        }
//                    }});
//                }
//                // HREF
//                else if (fields[i].type == "href") {
//                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key});
//                }
//                // MULTIHREF & OBJECTS
//                else if (fields[i].type == "multihref" || fields[i].type == "objects") {
//                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (record) {
//
//                        if (record.length > 0) {
//                            return record.join("<br />");
//                        }
//                    }});
//                }
//                // PASSWORD
//                else if (fields[i].type == "password") {
//                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (record) {
//                        return "**********";
//                    }});
//                }
//                // LINK
//                else if (fields[i].type == "link") {
//                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key});
//                }
//                // MULTISELECT
//                else if (fields[i].type == "multiselect") {
//                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (record) {
//                        if (record.length > 0) {
//                            return record.join(",");
//                        }
//                    }});
//                }
//                // TABLE
//                else if (fields[i].type == "table") {
//                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (record) {
//
//                        if (record && record.length > 0) {
//                            var table = '<table cellpadding="2" cellspacing="0" border="1">';
//                            for (var i = 0; i < record.length; i++) {
//                                table += '<tr>';
//                                for (var c = 0; c < record[i].length; c++) {
//                                    table += '<td>' + record[i][c] + '</td>';
//                                }
//                                table += '</tr>';
//                            }
//                            table += '</table>';
//                            return table;
//                        }
//                        return "";
//                    }});
//                }
//                // DEFAULT
//                else {
//                    gridColumns.push({header: ts(fields[i].label), sortable: true, dataIndex: fields[i].key});
//                }
//
//                // is visible or not
//                gridColumns[gridColumns.length-1].hidden = !fields[i].visibleSearch;
//            }
//        }
//
//        // filters
//        // add filters
//        var selectFilterFields;
//        var configuredFilters = [];
//
//        for (var i = 0; i < fields.length; i++) {
//            if (in_array(fields[i].type, validFieldTypes)) {
//                store = null;
//                selectFilterFields = null;
//
//                if (fields[i].type == "input" || fields[i].type == "textarea" || fields[i].type == "wysiwyg") {
//                    configuredFilters.push({
//                        type: 'string',
//                        dataIndex: fields[i].key
//                    });
//                } else if (fields[i].type == "numeric" || fields[i].type == "slider") {
//                    configuredFilters.push({
//                        type: 'numeric',
//                        dataIndex: fields[i].key
//                    });
//                } else if (fields[i].type == "date" || fields[i].type == "datetime") {
//                    configuredFilters.push({
//                        type: 'date',
//                        dataIndex: fields[i].key
//                    });
//                } else if (fields[i].type == "select" || fields[i].type == "country" || fields[i].type == "language") {
//                    selectFilterFields = [];
//
//                    store = new Ext.data.JsonStore({
//                        autoDestroy: true,
//                        root: 'store',
//                        fields: ['key',"value"],
//                        data: fields[i].config
//                    });
//
//                    store.each(function (rec) {
//                        selectFilterFields.push(rec.data.value);
//                    });
//
//                    configuredFilters.push({
//                        type: 'list',
//                        dataIndex: fields[i].key,
//                        options: selectFilterFields
//                    });
//                } else if (fields[i].type == "checkbox") {
//                    configuredFilters.push({
//                        type: 'boolean',
//                        dataIndex: fields[i].key
//                    });
//                } else if (fields[i].type == "multiselect") {
//                    selectFilterFields = [];
//
//                    store = new Ext.data.JsonStore({
//                        autoDestroy: true,
//                        root: 'options',
//                        fields: ['key',"value"],
//                        data: fields[i].layout
//                    });
//
//                    store.each(function (rec) {
//                        selectFilterFields.push(rec.data.value);
//                    });
//
//                    configuredFilters.push({
//                        type: 'list',
//                        dataIndex: fields[i].key,
//                        options: selectFilterFields
//                    });
//                }
//            }
//        }
//
//        // filters
//        var gridfilters = new Ext.ux.grid.GridFilters({
//            encode: true,
//            local: false,
//            filters: configuredFilters
//        });
        
        var fields = Ext.decode(response.responseText);
        var gridHelper = new pimcore.object.helpers.grid(selectedClass, fields, "/admin/search/search/find");
        this.store = gridHelper.getStore();
        var gridColumns = gridHelper.getGridColumns();
        var gridfilters = gridHelper.getGridFilters();


        this.getGridPanel(gridColumns, gridfilters);
    },
    
    initDefaultStore: function () {
        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            root: "data",
            url: "/admin/search/search/find",
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
    
    getGridPanel: function (columns, gridfilters) {
        
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
        
        this.resultPanel.removeAll();
        this.resultPanel.add(this.gridPanel);
        this.resultPanel.doLayout();
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
        
        this.pagingtoolbar.moveFirst();
    }
});