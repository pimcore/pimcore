
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
 
            var predefinedProperiesStore = new Ext.data.JsonStore({
                url: '/admin/' + this.type + '/get-predefined-properties',
                fields: ["id","name","description","key","type","data","config","inheritable",{name:"translatedName",convert: function(v, rec){

                    return ts(rec.name);

                    /*
                    var text = "<b>" + ts(rec.name) + "</b>";
                    if(!empty(rec.description)) {
                        text += ts(rec.description);
                    }
                    return text;
                    */
                }}],
                root: "properties"
            });

            var predefinedcombo = new Ext.form.ComboBox({
                name: "type",
                xtype: "combo",
                displayField:'translatedName',
                valueField: "id",
                store: predefinedProperiesStore,
                editable: false,
                triggerAction: 'all',
                listWidth: 200,
                emptyText: t("select_a_property"),
                listClass: "pimcore_predefined_property_select"
            });



 
            var propertyTypes = new Ext.data.SimpleStore({
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
                fieldLabel: t('type'),
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
                            config: property.config
                        });
                    }
                }
            }

            var store = new Ext.data.GroupingStore({
                autoDestroy: true,
                data: {properties: storeData},
                sortInfo:{field: 'inherited', direction: "ASC"},
                reader: new Ext.data.JsonReader({
                    root: 'properties',
                    fields: ['name','type',{name: "data", type: "string", convert: function (v, rec) {
                        if (rec.type == "document" || rec.type == "asset" || rec.type == "object") {
                            var type = rec.type;
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
                    }},"inherited","all",{name: 'inheritable', type: 'bool', mapping: "inheritable"}, "config"]
                }),
                groupField: 'inherited'
            });

            var checkColumn = new Ext.grid.CheckColumn({
                header: t("inheritable"),
                dataIndex: 'inheritable'
            });
 
            this.propertyGrid = new Ext.grid.EditorGridPanel({
                autoScroll: true,
                region: "center",
                reference: this,
                trackMouseOver: true,
                store: store,
                tbar: [{
                    xtype: "tbtext",
                    text: t('add_a_predefined_property_set') + " "
                },predefinedcombo,{
                    xtype: "button",
                    handler: this.addSetFromPredefined.bind(this, predefinedcombo, predefinedProperiesStore),
                    iconCls: "pimcore_icon_add"
                },{
                    xtype: "tbspacer",
                    width: 20
                },"-",{
                    xtype: "tbspacer",
                    width: 20
                },{
                    xtype: "tbtext",
                    text: t('add_a_custom_property') + " "
                },customKey, customType, {
                    xtype: "button",
                    handler: this.addSetFromUserDefined.bind(this, customKey, customType),
                    iconCls: "pimcore_icon_add"
                }],
                plugins: checkColumn,
                clicksToEdit: 1,
                view: new Ext.grid.GroupingView({
                    groupTextTpl: '{text}',
                    listeners: {
                        rowupdated: this.updateRows.bind(this, "rowupdated"),
                        refresh: this.updateRows.bind(this, "refresh")
                    }
                }),
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
                        editor: new Ext.form.TextField({
                            allowBlank: false
                        }),
                        sortable: true,
                        width: 230
                    },
                    {
                        id: "property_value_col",
                        header: t("value"),
                        dataIndex: 'data',
                        getCellEditor: this.getCellEditor.bind(this),
                        editable: true,
                        renderer: this.getCellRenderer.bind(this),
                        listeners: {
                            "mousedown": this.cellMousedown.bind(this)
                        }
                    },
                    checkColumn,
                    {
                        xtype: 'actioncolumn',
                        width: 30,
                        items: [{
                            tooltip: t('open'),
                            icon: "/pimcore/static/img/icon/pencil_go.png",
                            handler: function (grid, rowIndex) {
                                var pData = grid.getStore().getAt(rowIndex).data;
                                if(pData.all && pData.all.data) {
                                    if(pData.all.data.id) {
                                        pimcore.helpers.openElement(pData.all.data.id, pData.type, pData.all.data.type);
                                    }
                                    else if (pData.all.data.o_id) {
                                        pimcore.helpers.openElement(pData.all.data.o_id, pData.type, pData.all.data.o_type);
                                    }
                                }
                            }.bind(this),
                            getClass: function(v, meta, rec) {  // Or return a class from a function
                                if(rec.get('type') != "object" && rec.get('type') != "document" && rec.get('type') != "asset") {
                                    return "pimcore_hidden";
                                }
                            }
                        }]
                    },
                    {
                        xtype: 'actioncolumn',
                        width: 30,
                        items: [{
                            tooltip: t('delete'),
                            icon: "/pimcore/static/img/icon/cross.png",
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
 
            store.on("update", this.updateRows.bind(this));
            this.propertyGrid.on("viewready", this.updateRows.bind(this));
            this.propertyGrid.on("afterrender", function() {
                this.setAutoScroll(true);
            });
            this.propertyGrid.on("beforeedit", function (e) {
                if (e.grid.getStore().getAt(e.row).data.inherited) {
                    return false;
                }
                return true;
            });
            this.propertyGrid.on("rowcontextmenu", function (grid, rowIndex, event) {
                
                var propertyData = grid.getStore().getAt(rowIndex).data;
                
                if (propertyData.inherited) {
                    event.stopEvent();
                    return;
                }
 
                $(grid.getView().getRow(rowIndex)).animate( { backgroundColor: '#E0EAEE' }, 100).animate( { backgroundColor: '#fff' }, 400);
                
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
                                        pimcore.helpers.openElement(pData.all.data.o_id, pData.type, pData.all.data.o_type);
                                    }
                                }
                            }.bind(this, grid, rowIndex)
                        }));
                    }
                }
 
                event.stopEvent();
                menu.showAt(event.getXY())
            }.bind(this));

            this.layout = new Ext.Panel({
                title: t('properties'),
                border: false,
                layout: "border",
                iconCls: "pimcore_icon_tab_properties",
                items: [this.propertyGrid]
            });
        }
 
        return this.layout;
    },
 
    getTypeRenderer: function (value, metaData, record, rowIndex, colIndex, store) {
 
        return '<div style="background: url(/pimcore/static/img/icon/' + value + '.png) center center no-repeat; height: 16px;" name="' + record.data.name + '">&nbsp;</div>';
    },
 
    getCellRenderer: function (value, metaData, record, rowIndex, colIndex, store) {
 
        var data = store.getAt(rowIndex).data;
        var type = data.type;
 
        if (type == "document" || type == "asset" || type == "object") {
            if (value && data.inherited == false) {
                return '<div class="pimcore_property_droptarget">' + value + '</div>';
            }
            else if (data.inherited == false) {
                return '<div class="pimcore_property_droptarget">&nbsp;</div>';
            }
        } else if (type == "bool" && data.inherited == false) {
            metaData.css += ' x-grid3-check-col-td';
            return String.format('<div class="x-grid3-check-col{0}" style="background-position:10px center;">&#160;</div>', value ? '-on' : '');
        }
 
        return value;
    },

    cellMousedown: function (col, grid, rowIndex, event) {

        // this is used for the boolean field type
        
        var store = grid.getStore();
        var record = store.getAt(rowIndex);
        var data = record.data;
        var type = data.type;

        if (type == "bool") {
            record.set("data", !record.data.data);
        }
    },
 
    getCellEditor: function (rowIndex) {
 
        var store = this.propertyGrid.getStore();
        var data = store.getAt(rowIndex).data;
        var value = data.all;
 
        var type = data.type;
        var property;
 
        if (type == "text") {
            property = new Ext.form.TextField();
        }
        else if (type == "document" || type == "asset" || type == "object") {
 
            property = new Ext.form.TextField({
                disabled: true,
                propertyGrid: this.propertyGrid,
                myRowIndex: rowIndex,
                style: {
                    visibility: "hidden"
                }
            });
        }
        else if (type == "bool") {
            property = new Ext.form.Checkbox();
            return false;
        }
        else if (type == "select") {
            var config = data.config;
            property = new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: config.split(",")
            });
        }
 
 
        return new Ext.grid.GridEditor(property);
    },
 
    updateRows: function (event) {
        var rows = Ext.get(this.propertyGrid.getEl().dom).query(".x-grid3-row");
 
        for (var i = 0; i < rows.length; i++) {
 
            try {
                var propertyName = Ext.get(rows[i]).query(".x-grid3-cell-first div div")[0].getAttribute("name");
                var storeIndex = this.propertyGrid.getStore().findExact("name", propertyName);
 
                var data = this.propertyGrid.getStore().getAt(storeIndex).data;

                // hide checkcolumn at inherited properties
                if (data.inherited == true) {
                    Ext.get(rows[i]).addClass("pimcore_properties_hidden_checkcol");
                }

                if(in_array(data.name, this.disallowedKeys)) {
                     Ext.get(rows[i]).addClass("pimcore_properties_hidden_row");
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
                                if(dataRow.type == data.node.attributes.elementType) {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                }
                                return Ext.dd.DropZone.prototype.dropNotAllowed;
                            }.bind(this, data),
 
                            onNodeDrop : function(myRowIndex, target, dd, e, data) {

                                var rec = this.propertyGrid.getStore().getAt(myRowIndex);

                                if(data.node.attributes.elementType != rec.get("type")) {
                                    return false;
                                }


                                rec.set("data", data.node.attributes.path);
                                rec.set("all",{
                                    data: {
                                        id: data.node.attributes.id,
                                        type: data.node.attributes.type
                                    }
                                });
                                
                                this.updateRows();
 
                                return true;
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
        var id = combo.getValue();
        var selectedData = data.getAt(data.findExact("id", id)).data;

        if(in_array(selectedData.key, this.disallowedKeys)) {
            Ext.MessageBox.alert(t("error"), t("name_is_not_allowed"));
        }

        this.add(selectedData.key, selectedData.type, selectedData.data, selectedData.config, false, selectedData.inheritable);
    },
 
    addSetFromUserDefined: function (customKey, customType) {
        if(in_array(customKey.getValue(), this.disallowedKeys)) {
            Ext.MessageBox.alert(t("error"), t("name_is_not_allowed"));
        }
        this.add(customKey.getValue(), customType.getValue(), false, false, false, true);
    },
 
    add: function (key, type, value, config, inherited, inheritable) {

        if(in_array(key, this.disallowedKeys)) {
            return;
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
 
        var newRecord = new store.recordType({
            name: key,
            data: value,
            type: type,
            inherited: false,
            inheritable: inheritable,
            config: config
        });
 
        store.add(newRecord);
 
        this.propertyGrid.getStore().groupBy("inherited", true);
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
            currentData = records[i];
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