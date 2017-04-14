
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
 
pimcore.registerNS("pimcore.element.properties");
pimcore.element.properties = Class.create({

    disallowedKeys: [],

    initialize: function(element, type) {
        this.element = element;
        this.type = type;
 
        this.definedFieldTypes = {};
    },
 
    getLayout: function () {
 
        if (this.layout == null) {
 
            var predefinedPropertiesStore = new Ext.data.Store({
                fields: [
                    "id","name","description","key","type","data","config","inheritable",
                    {
                        name:"translatedName",
                        convert: function(v, rec){
                            return ts(rec.name);
                        }
                    }
                ],

                proxy: {
                    type: 'ajax',
                    url: '/admin/element/get-predefined-properties?elementType=' + this.type,
                    reader: {
                        type: 'json',
                        rootProperty: "properties"
                    }
                }
                });

            var predefinedcombo = new Ext.form.ComboBox({
                name: "type",
                displayField:'name',
                valueField: "id",
                store: predefinedPropertiesStore,
                editable: false,
                triggerAction: 'all',
                listWidth: 200,
                emptyText: t("select_a_property"),
                listClass: "pimcore_predefined_property_select"
            });

            var propertyTypes = new Ext.data.ArrayStore({
                fields: ['id', 'name'],
                data: [
                    ["text", "Text"],
                    ["document", "Document"],
                    ["asset", "Asset"],
                    ["object", "Object"],
                    ["bool", "Checkbox"]
                ]
            });

            var customKey = new Ext.form.TextField({
                name: 'key',
                emptyText: t('key')
            });

            var customType = new Ext.form.ComboBox({
                name: "type",
                valueField: "id",
                displayField:'name',
                store: propertyTypes,
                editable: false,
                triggerAction: 'all',
                mode: "local",
                listWidth: 200,
                emptyText: t('type')
            });

            // prepare store data
            var property = null;
            var keys = Object.keys(this.element.data.properties);
            var key = null;
            var storeData = [];
 
            if (keys.length > 0) {
                for (var i = 0; i < keys.length; i++) {
                    key = keys[i];
                    property = this.element.data.properties[key];
 
                    if (property && typeof property == "object") {
                        storeData.push({
                            name: property.name,
                            type: property.type,
                            data: property.data,
                            inherited: property.inherited,
                            inheritable: property.inheritable,
                            all: property,
                            config: property.config,
                            description: property["description"]
                        });
                    }
                }
            }

            var store = new Ext.data.Store({
                autoDestroy: true,
                data: {properties: storeData},
                sortInfo:{field: 'inherited', direction: "ASC"},
                proxy: {
                    type: 'memory',
                    reader: {
                        type: 'json',
                        rootProperty: 'properties'
                    }
                },
                fields: ['name','description','type',{name: "data", type: "string", convert: function (v, rec) {
                    if (rec.data.type == "document" || rec.data.type == "asset" || rec.data.type == "object") {
                        var type = rec.data.type;
                        if (type == "document") {
                            if (v && typeof v == "object") {
                                return v.path + v.key;
                            }
                        }
                        else if (type == "asset") {
                            if (v && typeof v == "object") {
                                return v.path + v.filename;
                            }
                        }
                        else if (type == "object") {
                            if (v && typeof v == "object") {
                                return v.o_path + v.o_key;
                            }
                        }

                    }

                    return v;
                }},"inherited","all",{name: 'inheritable', type: 'bool', mapping: "inheritable"}, "config"],
                groupField: 'inherited',
                filters: [
                    function(item) {
                        if(in_array(item.get("name"), this.disallowedKeys)) {
                            return false;
                        }
                        return true;
                    }.bind(this)
                ]
            });

            var checkColumn = Ext.create('Ext.grid.column.Check', {
                header: t("inheritable"),
                dataIndex: 'inheritable',
                listeners: {
                    beforecheckchange: function (el, rowIndex, checked, eOpts) {
                        if(store.getAt(rowIndex).get("inherited")) {
                            return false;
                        }

                        return true;
                    }
                }
            });

            this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1,
                listeners: {
                    beforeedit: function(editor, context, eOpts) {
                        //need to clear cached editors of cell-editing editor in order to
                        //enable different editors per row
                        editor.editors.each(Ext.destroy, Ext);
                        editor.editors.clear();

                        if(context.record.get("inherited")) {
                            return false;
                        }
                    }
                }
            });

            this.propertyGrid = Ext.create('Ext.grid.Panel', {
                autoScroll: true,
                region: "center",
                //reference: this,
                sm:  Ext.create('Ext.selection.RowModel', {}),
                bufferedRenderer: false,
                trackMouseOver: true,
                store: store,
                bodyCls: "pimcore_editable_grid",
                plugins: [
                    this.cellEditing
                ],
                tbar: [predefinedcombo,{
                    xtype: "button",
                    handler: this.addSetFromPredefined.bind(this, predefinedcombo, predefinedPropertiesStore),
                    iconCls: "pimcore_icon_add"
                },"-",{
                    xtype: "tbtext",
                    text: t('add_a_custom_property') + " "
                },
                customKey,
                customType, {
                    xtype: "button",
                    handler: this.addSetFromUserDefined.bind(this, customKey, customType),
                    iconCls: "pimcore_icon_add"
                }],
                //plugins: checkColumn,
                clicksToEdit: 1,
                features: [
                    Ext.create('Ext.grid.feature.Grouping', {
                        listeners: {
                            rowupdated: this.updateRows.bind(this, "rowupdated"),
                            refresh: this.updateRows.bind(this, "refresh")
                        }
                    })
                ],
                autoExpandColumn: "property_value_col",
                columnLines: true,
                stripeRows: true,
                columns: [
                    {
                        header: t("type"),
                        dataIndex: 'type',
                        editable: false,
                        width: 40,
                        renderer: this.getTypeRenderer.bind(this),
                        sortable: true
                    },
                    {
                        header: t('inherited'),
                        dataIndex: 'inherited',
                        editable: false,
                        hidden: true,
                        sortable: true
                    },
                    {
                        header: t("name"),
                        dataIndex: 'name',
                        getEditor: function() {
                            return new Ext.form.TextField({
                                allowBlank: false
                            });
                        },
                        sortable: true,
                        width: 230
                    },
                    {
                        header: t("description"),
                        dataIndex: 'description',
                        editable: false,
                        sortable: true,
                        width: 230
                    },
                    {
                        //id: "property_value_col",
                        header: t("value"),
                        dataIndex: 'data',
                        flex: 1,
                        getEditor: this.getCellEditor.bind(this),
                        editable: true,
                        renderer: this.getCellRenderer.bind(this)
                        ,
                        listeners: {
                            "mousedown": this.cellMousedown.bind(this)
                        }
                    },
                    checkColumn,
                    {
                        xtype: 'actioncolumn',
                        width: 40,
                        items: [{
                            tooltip: t('open'),
                            icon: "/pimcore/static6/img/flat-color-icons/cursor.svg",
                            handler: function (grid, rowIndex) {
                                var pData = grid.getStore().getAt(rowIndex).data;
                                if(pData.all && pData.all.data) {
                                    if(pData.all.data.id) {
                                        pimcore.helpers.openElement(pData.all.data.id, pData.type, pData.all.data.type);
                                    }
                                    else if (pData.all.data.o_id) {
                                        pimcore.helpers.openElement(pData.all.data.o_id, pData.type,
                                                                                        pData.all.data.o_type);
                                    }
                                }
                            }.bind(this),
                            getClass: function(v, meta, rec) {  // Or return a class from a function
                                if(rec.get('type') != "object" && rec.get('type') != "document"
                                                                            && rec.get('type') != "asset") {
                                    return "pimcore_hidden";
                                }
                            }
                        }]
                    },
                    {
                        xtype: 'actioncolumn',
                        width: 40,
                        items: [{
                            tooltip: t('delete'),
                            icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);
                            }.bind(this),
                            getClass: function(v, meta, rec) {  // Or return a class from a function
                                if (rec.get('inherited')) {
                                    return "pimcore_hidden";
                                }
                            }
                        }]
                    }
                ]
            });

            this.propertyGrid.getView().on("refresh", this.updateRows.bind(this, "view-refresh"));
            this.propertyGrid.getView().on("afterrender", this.updateRows.bind(this, "view-afterrender"));
            this.propertyGrid.getView().on("viewready", this.updateRows.bind(this, "view-viewready"));

            this.propertyGrid.on("viewready", this.updateRows.bind(this));
            this.propertyGrid.on("afterrender", function() {
                this.setAutoScroll(true);
            });

            this.propertyGrid.on("rowcontextmenu", function ( grid, record, tr, rowIndex, e, eOpts ) {
                
                var propertyData = grid.getStore().getAt(rowIndex).data;
                
                if (propertyData.inherited) {
                    e.stopEvent();
                    return;
                }

                var menu = new Ext.menu.Menu();
 
                menu.add(new Ext.menu.Item({
                    text: t('delete'),
                    iconCls: "pimcore_icon_delete",
                    handler: function (grid, index) {
                        var name = grid.getStore().getAt(index).data.name;
                        grid.getStore().removeAt(index);
                    }.bind(this, grid, rowIndex)
                }));
                
                if(propertyData.type == "object" || propertyData.type == "document" || propertyData.type == "asset") {
                    if(propertyData.data) {
                        menu.add(new Ext.menu.Item({
                            text: t('open'),
                            iconCls: "pimcore_icon_open",
                            handler: function (grid, index) {
                                var pData = grid.getStore().getAt(index).data;
                                if(pData.all && pData.all.data) {
                                    if(pData.all.data.id) {
                                        pimcore.helpers.openElement(pData.all.data.id, pData.type, pData.all.data.type);
                                    }
                                    else if (pData.all.data.o_id) {
                                        pimcore.helpers.openElement(pData.all.data.o_id, pData.type,
                                                                                                pData.all.data.o_type);
                                    }
                                }
                            }.bind(this, grid, rowIndex)
                        }));
                    }
                }
 
                e.stopEvent();
                menu.showAt(e.pageX, e.pageY);
            }.bind(this));

            this.layout = new Ext.Panel({
                title: t('properties'),
                border: false,
                layout: "border",
                iconCls: "pimcore_icon_properties",
                items: [this.propertyGrid]
            });
        }
 
        return this.layout;
    },
 
    getTypeRenderer: function (value, metaData, record, rowIndex, colIndex, store) {
 
        return '<div class="pimcore_icon_' + value + '" name="' + record.data.name + '">&nbsp;</div>';
    },
 
    getCellRenderer: function (value, metaData, record, rowIndex, colIndex, store) {
 
        var data = store.getAt(rowIndex).data;
        var type = data.type;

        if (!value) {
            value = "";
        }

        if (type == "document" || type == "asset" || type == "object") {
            if (value && data.inherited == false) {
                return '<div class="pimcore_property_droptarget">' + value + '</div>';
            }
            else if (data.inherited == false) {
                return '<div class="pimcore_property_droptarget">&nbsp;</div>';
            }
        } else if (type == "bool" && data.inherited == false) {
            if (value) {
                return '<div style="text-align: left"><div role="button" class="x-grid-checkcolumn x-grid-checkcolumn-checked" style=""></div></div>';
            } else {
                return '<div style="text-align: left"><div role="button" class="x-grid-checkcolumn" style=""></div></div>';
            }
        }
 
        return value;
    },

    cellMousedown: function (view, cell, rowIndex, cellIndex, e) {

        // this is used for the boolean field type
        
        var store = this.propertyGrid.getStore();
        var record = store.getAt(rowIndex);
        var data = record.data;
        var type = data.type;

        if (type == "bool") {
            record.set("data", !record.data.data);
        }
    },
 
    getCellEditor: function (record, defaultField ) {
        var data = record.data;
        var value = data.all;
 
        var type = data.type;
        var property;
 
        if (type == "text") {
            property = new Ext.form.TextField();
        }
        else if (type == "document" || type == "asset" || type == "object") {
            //no editor needed here
        }
        else if (type == "bool") {
            //no editor needed here
        }
        else if (type == "select") {
            var config = data.config;
            property = new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: config.split(",")
            });
        }

        return property;
    },
 
    updateRows: function (event) {
        var rows = Ext.get(this.propertyGrid.getEl().dom).query(".x-grid-row");
        var parentTable;

        for (var i = 0; i < rows.length; i++) {
 
            try {
                var propertyName = Ext.get(rows[i]).query(".x-grid-cell-first div div")[0].getAttribute("name");
                var storeIndex = this.propertyGrid.getStore().findExact("name", propertyName);
 
                var data = this.propertyGrid.getStore().getAt(storeIndex).data;

                // hide checkcolumn at inherited properties
                if (data.inherited == true) {
                    Ext.get(rows[i]).addCls("pimcore_properties_hidden_checkcol");
                }

                if (data.type == "document" || data.type == "asset" || data.type == "object") {
                    if (data.inherited == false) {
                        // add dnd support 
                        var dd = new Ext.dd.DropZone(rows[i], {
                            ddGroup: "element",
 
                            getTargetFromEvent: function(e) {
                                return this.getEl();
                            },
 
                            onNodeOver : function(dataRow, target, dd, e, data) {
                                var record = data.records[0];
                                var data = record.data;

                                if(dataRow.type == data.elementType) {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                }
                                return Ext.dd.DropZone.prototype.dropNotAllowed;
                            }.bind(this, data),
 
                            onNodeDrop : function(myRowIndex, target, dd, e, data) {
                                try {
                                    var record = data.records[0];
                                    var data = record.data;

                                    var rec = this.propertyGrid.getStore().getAt(myRowIndex);

                                    if(data.elementType != rec.get("type")) {
                                        return false;
                                    }


                                    rec.set("data", data.path);
                                    rec.set("all",{
                                        data: {
                                            id: data.id,
                                            type: data.type
                                        }
                                    });

                                    this.updateRows();

                                    return true;
                                } catch (e) {
                                    console.log(e);
                                }
                            }.bind(this, storeIndex)
                        });
                    }
                }
            }
            catch (e) {
                console.log(e);
            }
        }
    },
 
    addSetFromPredefined: function (combo, data) {
        try {
            var id = combo.getValue();
            var selectedData = data.getAt(data.findExact("id", id)).data;

            if (in_array(selectedData.key, this.disallowedKeys)) {
                Ext.MessageBox.alert(t("error"), t("name_is_not_allowed"));
            }

            this.add(selectedData.key, selectedData.type, selectedData.data, selectedData.config, false,
                selectedData.inheritable, selectedData.description);
        } catch (e) {
            console.log(e);
        }
    },
 
    addSetFromUserDefined: function (customKey, customType) {
        try {
            if (in_array(customKey.getValue(), this.disallowedKeys)) {
                Ext.MessageBox.alert(t("error"), t("name_is_not_allowed"));
            }
            this.add(customKey.getValue(), customType.getValue(), false, false, false, true);
        } catch (e) {
            console.log(e);
        }
    },
 
    add: function (key, type, value, config, inherited, inheritable, description) {

        if(in_array(key, this.disallowedKeys)) {
            return;
        }

        if(typeof description != "string") {
            description = "";
        }

        var store = this.propertyGrid.getStore();
 
        // check for duplicate name
        var dublicateIndex = store.findBy(function (key, record, id) {
            if (record.data.name.toLowerCase() == key.toLowerCase()) {
                return true;
            }
            return false;
        }.bind(this, key));
 
 
        if (dublicateIndex >= 0) {
            if (store.getAt(dublicateIndex).data.inherited == false) {
                Ext.MessageBox.alert(t("error"), t("name_already_in_use"));
                return;
            }
        }
 
        // check for empty key & type
        if (key.length < 2 || type.length < 1) {
            Ext.MessageBox.alert(t("error"), t("name_and_key_must_be_defined"));
            return;
        }
 
 
        if (!value) {
            if (type == "bool") {
                value = true;
            }
            if (type == "document" || type == "asset" || type == "object") {
                value = "";
            }
            if (type == "text") {
                value = "";
            }
            value = "";
        }
 
        if (typeof inheritable != "boolean") {
            inheritable = true;
        }

        var model = store.getModel();
        var newRecord = new model({
            name: key,
            data: value,
            type: type,
            inherited: false,
            inheritable: inheritable,
            config: config,
            description: description
        });


        store.add(newRecord);
 
        this.propertyGrid.getStore().group("inherited");
        this.propertyGrid.getView().refresh();
    },
 
    getValues : function () {
 
        if (!this.propertyGrid.rendered) {
            throw "properties not available";
        }
 
        var values = {};
        var store = this.propertyGrid.getStore();
        store.commitChanges();
 
        var records = store.getRange();
 
        for (var i = 0; i < records.length; i++) {
            var currentData = records[i];
            if (currentData) {
                if (!currentData.data.inherited) {
                    values[currentData.data.name] = {
                        data: currentData.data.data,
                        type: currentData.data.type,
                        inheritable: currentData.data.inheritable
                    };
                }
            }
        }
 
 
        return values;
    }
 
});