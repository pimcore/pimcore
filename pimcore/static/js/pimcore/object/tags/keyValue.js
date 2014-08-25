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

pimcore.registerNS("pimcore.object.tags.keyValue");
pimcore.object.tags.keyValue = Class.create(pimcore.object.tags.abstract, {

    type: "keyValue",


    initialize: function (data, fieldConfig) {

        this.originalData = JSON.parse(JSON.stringify(data));
        this.fieldConfig = fieldConfig;

        var fields = [];

        fields.push("id");
        fields.push("groupName");
        fields.push("key");
        fields.push("keyName");
        fields.push("keyDesc");
        fields.push("value");
        fields.push("translated");
        fields.push("type");
        fields.push("possiblevalues");
        fields.push("inherited");
        fields.push("source");
        fields.push("mandatory");
        fields.push("altSource");
        fields.push("altValue");
        if (fieldConfig.metaVisible) {
            fields.push("metadata");
        }
        // this.visibleFields = fields;

        this.store = new Ext.data.ArrayStore({
            data: [],
            listeners: {
                remove: function() {
                    this.dataChanged = true;
                }.bind(this),
                clear: function () {
                    this.dataChanged = true;
                }.bind(this),
                update: function(store, record, operation) {
                    this.dataChanged = true;
                    if (this.isDeleteOperation) {
                        // do nothing
                    } else {
                        if (record.data.inherited) {
                            // changed inherited property, iterate over the store and delete all
                            // properties with the same keys
                            var count = store.getCount();
                            for(var i= count - 1; i >= 0; i--) {
                                var r = store.getAt(i);
                                if (r.data.id != record.data.id && r.data.key == record.data.key) {
                                    store.remove(r);
                                }
                            }
                        }

                        record.set("inherited", false);
                        if (record.data.type == "translated") {
                            // whoooo, we have to go to the server and ask for a new translation
                            this.translate(record);
                        } else {
                            record.set("translated", record.data.value);
                        }
                    }
                }.bind(this)
            },
            fields: fields,
            sortInfo : { field: "key", direction: "ASC" }
        });

        for (var i = 0; i < data.length; i++) {
            var pair = data[i];
            var add = true;
            if (this.fieldConfig.multivalent && pair.inherited) {
                for (var k = 0; k < data.length; k++) {
                    var otherPair = data[k];
                    if (otherPair.key == pair.key && !otherPair.inherited) {
                        add = false;
                        break;
                    }
                }
            }

            if (add) {
                this.store.add(new this.store.recordType(pair));
            }
        }

        this.store.sort("description", "ASC");
        this.updateMandatoryKeys();

        this.store.on("add", function() {
            this.dataChanged = true;
        }.bind(this)
        );
    },


    translate: function(record) {
        Ext.Ajax.request({
            url: "/admin/key-value/translate",
            params: {
                "recordId": record.id,
                "keyId" : record.data.key,
                "objectId" : record.data.o_id,
                "text": record.data.value
            },
            success: this.translationReceived.bind(this),
            failure: function() {
                alert("translation failed");
            }.bind(this)
        });

    },

    translationReceived: function (response) {
        var translation = Ext.decode(response.responseText);
        if (translation.success) {
            var recordId = translation.recordId;
            var record = this.store.getById(recordId);
            if (record.data.value == translation.text) {
                record.set("translated", translation.translated);
            }
        }
    },

    getGridColumnEditor: function(field) {
        var editorConfig = {};

        if (field.config) {
            if (field.config.width) {
                if (intval(field.config.width) > 10) {
                    editorConfig.width = field.config.width;
                }
            }
        }

        if(field.layout.noteditable) {
            return null;
        }

        if (field.layout.gridType == "text" || field.layout.gridType == "translated") {
            return new Ext.form.TextField(editorConfig);
            // }
        } else if (field.layout.gridType == "select"  || field.layout.gridType == "translatedSelect") {
            var store = new Ext.data.JsonStore({
                autoDestroy: true,
                root: 'options',
                fields: ['key',"value"],
                data: field.layout
            });

            editorConfig = Object.extend(editorConfig, {
                store: store,
                triggerAction: "all",
                editable: false,
                mode: "local",
                valueField: 'value',
                displayField: 'key'
            });

            return new Ext.form.ComboBox(editorConfig);
        } else if (field.layout.gridType == "number") {
            return new Ext.form.NumberField();
        } else if (field.layout.gridType == "bool") {
            return false;
        }

        return  null;
    },

    isDirty: function()  {
        //console.log(this.dataChanged);
        return this.dataChanged;
    },


    getLayoutEdit: function () {

        var autoHeight = true;

        var gridWidth = 0;
        var gridHeight = 150;
        var keyWidth = 150;
        var descWidth = 300;
        var groupWidth = 200;
        var groupDescWidth = 200;
        var valueWidth = 600;
        var metaWidth = 200;
        var maxHeight = 190;
        var metawidth = 100;

        if (this.fieldConfig.maxheight > 0) {
            maxHeight = this.fieldConfig.maxheight;
        }

        if (this.fieldConfig.keyWidth) {
            keyWidth = this.fieldConfig.keyWidth;
        }

        if (this.fieldConfig.groupWidth) {
            groupWidth = this.fieldConfig.groupWidth;
        }

        if (this.fieldConfig.groupDescWidth) {
            groupDescWidth = this.fieldConfig.groupDescWidth;
        }

        if (this.fieldConfig.valueWidth) {
            valueWidth = this.fieldConfig.valueWidth;
        }

        if (this.fieldConfig.descWidth) {
            descWidth = this.fieldConfig.descWidth;
        }

        if (this.fieldConfig.metawidth) {
            metawidth = this.fieldConfig.metawidth;
        }

        var readOnly = false;
        // css class for editorGridPanel
        var cls = 'object_field';

        var columns = [];

        // var visibleFields = ['key','description', 'value','type','possiblevalues'];
        var visibleFields = ['group', 'groupDesc', 'keyName', 'keyDesc', 'value', 'unit'];
        if (this.fieldConfig.metaVisible) {
            visibleFields.push('metadata');
        }

        for(var i = 0; i < visibleFields.length; i++) {
            var editor = null;
            var editable = false;
            var renderer = null;
            var cellEditor = null;
            var col = visibleFields[i];
            var listeners = null;
            var colWidth = keyWidth;


            if (i == 0) {
                renderer = this.getCellRenderer.bind(this);
                listeners =  {
                    "dblclick": this.keycellMousedown.bind(this)
                };
            }

            if (col == "group") {
                colWidth = groupWidth;
            } else if (col == "groupDesc") {
                colWidth = groupDescWidth;
            } else if (col == "metadata") {
                colWidth = metawidth;
            }

            if (col == 'value') {
                colWidth = valueWidth;
                editable = true;
                cellEditor = this.getCellEditor.bind(this, col);
                renderer = this.getCellRenderer.bind(this);
                listeners =  {
                    "mousedown": this.cellMousedown.bind(this)
                };
            } else if (col == "metadata") {
                editable = true;
                cellEditor = this.getCellEditor.bind(this, col);
            } else if (col == "keyName") {
                renderer = function (value, metaData, record, rowIndex, colIndex, store) {
                    if (record.data.mandatory) {
                        value = value + ' <span style="color:red;">*</span>';
                    }
                    return value;
                }

            }

            gridWidth += colWidth;

            var columnConfig = {
                header: t("keyvalue_tag_col_" + visibleFields[i]),
                dataIndex: visibleFields[i],
                width: colWidth,
                editor: editor,
                editable: editable,
                renderer: renderer,
                getCellEditor: cellEditor,
                listeners: listeners
            };
            columns.push(columnConfig);
        }


        var actionColWidth = 30;
        if(!readOnly) {
            columns.push({
                xtype: 'actioncolumn',
                width: actionColWidth,
                hideable: false,
                items: [
                    {
                        getClass: function (v, meta, rec) {
                            var klass = "pimcore_action_column";
                            if (!rec.data.inherited) {
                                klass +=  " pimcore_icon_cross";
                            }
                            return klass;

                        },
                        tooltip: t('remove'),
                        // icon: "/pimcore/static/img/icon/cross.png",
                        handler: function (grid, rowIndex) {
                            var store = grid.getStore();
                            var record = store.getAt(rowIndex);
                            var data = record.data;
                            if (data.inherited) {
                                record.set("inherited", false);
                            } else {
                                if (data.altSource && !this.fieldConfig.multivalent) {
                                    this.isDeleteOperation = true;
                                    record.set("inherited", true);
                                    record.set("value", data.altValue);
                                    record.set("source", data.altSource);
                                    this.isDeleteOperation = false;
                                } else {
                                    var key = data.key;

                                    store.removeAt(rowIndex);

                                    if (this.fieldConfig.multivalent) {
                                        // check if this was the last non-inherited row
                                        var nonInheritedFound = false;
                                        var count = store.getCount();
                                        for (var i = 0; i < count; i++) {
                                            var pair = store.getAt(i).data;
                                            if (pair.key == key && !pair.inherited) {
                                                nonInheritedFound = true;
                                            }
                                        }

                                        if (!nonInheritedFound) {
                                            // we have to add the inherited pairs
                                            for (var i = 0; i < this.originalData.length; i++) {
                                                var pair = this.originalData[i];
                                                if (pair.key == key && pair.inherited) {
                                                    var newpair = JSON.parse(JSON.stringify(pair));
                                                    this.store.add(new this.store.recordType(newpair));
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }.bind(this)
                    }
                ]
            });
        }

        gridWidth += actionColWidth;

        var configuredFilters = [
            {
                type: "string",
                dataIndex: "group"
            },
            {
                type: "string",
                dataIndex: "description"
            },
            {
                type: "string",
                dataIndex: "value"
            }
        ];

        if (this.fieldConfig.metaVisible) {
            configuredFilters.push(            {
                    type: "string",
                    dataIndex: "metadata"
                }
            );
        }
        // filters
        var gridfilters = new Ext.ux.grid.GridFilters({
            encode: true,
            local: true,
            filters: configuredFilters
        });


        var plugins = [gridfilters];



        this.component = new Ext.grid.EditorGridPanel({
            clicksToEdit: 1,
            store: this.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: true
                },
                columns: columns
            }),
            viewConfig: {
                markDirty: false
            },
            cls: cls,
            width: gridWidth,
            stripeRows: true,
            plugins: plugins,
            title: t('keyvalue_tag_title'),
            tbar: {
                items: [
                    {
                        xtype: "tbspacer",
                        width: 20,
                        height: 16
                    },
                    {
                        xtype: "tbtext",
                        text: "<b>" + this.fieldConfig.title + "</b>"
                    },
                    "->",
                    {
                        xtype: "button",
                        iconCls: "pimcore_icon_delete",
                        handler: this.empty.bind(this)
                    },
                    {
                        xtype: "button",
                        iconCls: "pimcore_icon_add",
                        handler: this.openSearchEditor.bind(this)
                    }
                ],
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            autoHeight: autoHeight,
            maxHeight: 10,
            bodyCssClass: "pimcore_object_tag_objects"
        });

        this.component.on("afteredit", function() {
            this.dataChanged = true;
        }.bind(this));



        return this.component;
    },

    keycellMousedown: function (col, grid, rowIndex, event) {

        var store = grid.getStore();
        var record = store.getAt(rowIndex);
        var data = record.data;


        pimcore.helpers.openObject(data.source, "object");
    },

    cellMousedown: function (col, grid, rowIndex, event) {


        var store = grid.getStore();
        var record = store.getAt(rowIndex);
        var data = record.data;

        var type = data.type;
        // this is used for the boolean field type
        if (type == "bool") {
            record.set("value", !record.data.value);
        }

    },

    getCellRenderer: function (value, metaData, record, rowIndex, colIndex, store) {
        var data = store.getAt(rowIndex).data;
        var type = data.type;

        if (colIndex == 0) {
            if (record.data.inherited) {
                metaData.css += " grid_value_inherited";
            }
        } else {
            if (colIndex == 3) {
                metaData.css += " grid_value_noedit";
            }
            if (this.isInvalid(record)) {
                metaData.css += " keyvalue_mandatory_violation";
            }

            if (type == "translated") {
                if (data.translated) {
                    return data.translated;
                }
            } else if (type == "bool") {
                metaData.css += ' x-grid3-check-col-td';
                return String.format('<div class="x-grid3-check-col{0}" style="background-position:10px center;">&#160;</div>', value ? '-on' : '');
            } else if (type == "range") {
                // render range value for list view [YouWe]
                var rangeObject = Ext.util.JSON.decode(value);
                if (typeof rangeObject == "object" && rangeObject.start != undefined && rangeObject.end != undefined) {
                    return rangeObject.start + ' to ' + rangeObject.end;
                }
                return '';

            } else if (type == "select"  || type == "translatedSelect") {
                var decodedValues = Ext.util.JSON.decode(data.possiblevalues);
                for (var i = 0;  i < decodedValues.length; i++) {

                    var val = decodedValues[i];
                    if (val.value == value) {
                        return (type == "translatedSelect") ? ts(val.key) : val.key;
                    }
                }
            }
        }

        return value;
    },

    /**
     * Update store data
     * @param data
     * @author Yasar Kunduz <y.kunduz@youwe.nl>
     */
    updateStoreData : function (data) {
        data.value = Ext.util.JSON.encode(data.value);
        this.store.loadData(data, true);
        this.dataChanged = true;
    },

    getCellEditor: function (col, rowIndex) {
        var parent = this;
        if (col == "metadata") {
            property = new Ext.form.TextField();
        } else {
            var store = this.store;
            var data = store.getAt(rowIndex).data;

            var type = data.type;
            var property;

            if (type == "text" || type =="translated") {
                property = new Ext.form.TextField();
            } else if (type == "number") {
                property = new Ext.form.NumberField();
            } else if (type == "bool") {
                property = new Ext.form.Checkbox();
                return false;
            } else if (type == "range") {
                // Added type range [YouWe]
                var rangeObject = data.value ? Ext.util.JSON.decode(data.value) : {start: '', end: ''};

                if (typeof rangeObject != "object" || rangeObject.start == undefined) {
                    rangeObject = {start: '', end: ''};
                }
                var rangeWindow = new Ext.Window(
                    {
                        title : ts('Range'),
                        modal: true,
                        items : [{
                            xtype: 'compositefield',
                            fieldLabel: ts('Range'),
                            width: 300,
                            style: 'padding:10px; line-height: 20px;',
                            items: [
                                new Ext.form.Label({text: ts('Start')}),
                                {
                                    xtype       : 'textfield',
                                    width       : 70,
                                    value       : rangeObject.start,
                                    enableKeyEvents : true,
                                    listeners : {
                                        change : function(el, e) {
                                            rangeObject.start = el.getValue();
                                        }
                                    },
                                    validator: function(val) {
                                        if (!Ext.isEmpty(val)) {
                                            return true;
                                        } else {
                                            return "Value cannot be empty";
                                        }
                                    }

                                },
                                new Ext.form.Label({text: ts('End')}),
                                {
                                    xtype       : 'textfield',
                                    width       : 70,
                                    value       : rangeObject.end,
                                    enableKeyEvents : true,
                                    listeners : {
                                        change : function(el, e) {
                                            rangeObject.end = el.getValue();
                                        }
                                    },
                                    validator: function(val) {
                                        if (!Ext.isEmpty(val)) {
                                            return true;
                                        } else {
                                            return "Value cannot be empty";
                                        }
                                    }
                                },
                                new Ext.Button({
                                    text: t("apply"),
                                    iconCls: "pimcore_icon_apply",
                                    listeners: {
                                        click: function(){
                                            data.value = rangeObject;
                                            parent.updateStoreData(data);
                                            rangeWindow.hide();
                                        }
                                    },
                                    enableToggle: true
                                })
                            ]
                        }],
                        frame: true
                    }
                );
                rangeWindow.show();
                return false;
            } else if (type == "select"  || type == "translatedSelect") {
                var values = [];
                var possiblevalues = data.possiblevalues;

                var storedata = [];

                var decodedValues = Ext.util.JSON.decode(possiblevalues);
                for (var i = 0;  i < decodedValues.length; i++) {
                    var val = decodedValues[i];
                    var entry = [val.value , val.key];
                    storedata.push(entry);
                }

                property = new Ext.form.ComboBox({
                    triggerAction: 'all',
                    editable: false,
                    mode: "local",
                    store: new Ext.data.ArrayStore({
                        id: 0,
                        fields: [
                            'id',
                            'label'
                        ],
                        data: storedata
                    }),
                    valueField: 'id',
                    displayField: 'label'

                });
            }
        }


        return new Ext.grid.GridEditor(property);
    },


    empty: function () {
        this.store.removeAll();
    },

    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        var value = [];

        var totalCount = this.store.data.length;

        for (var i = 0; i < totalCount; i++) {
            var record = this.store.getAt(i);
            value.push(record.data);
        }
        return value;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    openSearchEditor: function () {
        var selectionWindow = new pimcore.object.keyvalue.selectionwindow(this);
        selectionWindow.show();
    },


    handleSelectionWindowClosed: function() {
        // nothing to do
    },

    requestPending: function() {
        // nothing to do
    },

    handleAddKeys: function (response) {
        var data = Ext.decode(response.responseText);

        if(data && data.success) {
            for (var i=0; i < data.data.length; i++) {
                var keyDef = data.data[i];

                var totalCount = this.store.data.length;

                var addKey = true;
                for (var x = 0; x < totalCount; x++) {
                    var record = this.store.getAt(x);

                    if (!this.fieldConfig.multivalent) {
                        if (record.data.key == keyDef.id) {
                            addKey = false;
                            break;
                        }
                    }
                }

                if (addKey) {
                    var colData = {};
                    colData.key = keyDef.id;
                    colData.keyName = keyDef.name;
                    colData.type = keyDef.type;
                    colData.possiblevalues = keyDef.possiblevalues;
                    colData.keyDesc = keyDef.description;
                    colData.group = keyDef.groupName;
                    colData.groupDesc = keyDef.groupdescription;
                    colData.unit = keyDef.unit;
                    colData.mandatory = keyDef.mandatory;
                    this.store.add(new this.store.recordType(colData));

                    if (this.fieldConfig.multivalent) {
                        // iterate over the store and remove all inherited pairs
                        var count = this.store.getCount();
                        for (var k  = count - 1; k > 0; k--) {
                            var p = this.store.getAt(k).data;
                            if (p.key == keyDef.id && p.inherited) {
                                this.store.removeAt(k);
                            }
                        }
                    }
                }
            }

            this.updateMandatoryKeys();
        }
    },

    updateMandatoryKeys: function() {
        this.mandatoryKeyExists = false;
        var totalCount = this.store.data.length;

        for (var i = 0; i < totalCount; i++) {
            var record = this.store.getAt(i);
            if (record.data.mandatory) {
                this.mandatoryKeyExists = true;
                break;
            }
        }
    },


    isInvalid: function(record) {

        if (record.data.mandatory) {

            if (record.data.type == "text" || record.data.type == "translated" || record.data.type == "select" || record.data.type == "translatedSelect") {
                if (!record.data.value) {
                    return true;
                }
            } else if (record.data.type == "number") {
                var type = typeof(record.data.value);
                if (type !=  "number" && !(type == "string" && record.data.value.length > 0)) {
                    return true;
                }
            } else if (record.data.type == "range") {
                if (!record.data.value) {
                    return true;
                } else {
                    var rangeObject = Ext.util.JSON.decode(record.data.value);
                    if (typeof rangeObject == "object" && (rangeObject.start == undefined || rangeObject.end == undefined || rangeObject.start == '' || rangeObject.end == '')) {
                        return true;
                    }
                }
            }
        }
        return false;
    },

    isInvalidMandatory:function () {

        var totalCount = this.store.data.length;

        for (var i = 0; i < totalCount; i++) {
            var record = this.store.getAt(i);
            if (this.isInvalid(record)) {
                return true;
            }

        }

        return false;
    },


    isMandatory:function () {
        return this.mandatoryKeyExists;
    },


    getGridColumnConfig:function (field) {
        var renderer;
        if (field.layout.gridType == "bool") {
            return new Ext.grid.CheckColumn({
                header:ts(field.label),
                dataIndex:field.key,
                renderer:function (key, value, metaData, record, rowIndex, colIndex, store) {
                    this.applyPermissionStyle(key, value, metaData, record);

                    var multivalent = value instanceof  Array;
                    var inherited = record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited;

                    if (inherited && multivalent) {
                        metaData.css += " grid_value_inherited_locked";
                    } else if (inherited) {
                        metaData.css += " grid_value_inherited";
                    } else if (multivalent) {
                        metaData.css += " grid_value_locked";
                    }

                    metaData.css += ' x-grid3-check-col-td';
                    return String.format('<div class="x-grid3-check-col{0}">&#160;</div>', value ? '-on' : '');
                }.bind(this, field.key)
            });
        } else if (field.layout.gridType == "translated") {
            renderer = function (key, value, metaData, record) {
                var multivalent = value instanceof  Array;
                var inherited = record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited;

                if (inherited && multivalent) {
                    metaData.css += " grid_value_inherited_locked";
                } else if (inherited) {
                    metaData.css += " grid_value_inherited";
                } else if (multivalent) {
                    metaData.css += " grid_value_locked";
                }

                if (record.data["#kv-tr"][key] !== undefined) {
                    return record.data["#kv-tr"][key];
                } else {
                    return value;
                }
            }.bind(this, field.key);
            return {header:ts(field.label), sortable:true, dataIndex:field.key, renderer:renderer,
                editor:this.getGridColumnEditor(field)};
        } else {
            renderer = function (key, value, metaData, record) {
                var multivalent = value instanceof  Array;
                var inherited = record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited;

                if (inherited && multivalent) {
                    metaData.css += " grid_value_inherited_locked";
                } else if (inherited) {
                    metaData.css += " grid_value_inherited";
                } else if (multivalent) {
                    metaData.css += " grid_value_locked";
                }

                if (record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.css += " grid_value_inherited";
                }
                return value;
            }.bind(this, field.key);

            return {header:ts(field.label), sortable:true, dataIndex:field.key, renderer:renderer,
                editor:this.getGridColumnEditor(field)};
        }
    },

    applyGridEvents: function(grid, field) {
        grid.on("beforeedit", function(field, e) {
            if(e.field == field.key && e.value instanceof Array) {
                e.cancel = true;
                Ext.Msg.show({
                    title: t('keyvalue_data_locked_title'),
                    msg: t('keyvalue_data_locked_msg'),
                    buttons: Ext.Msg.OK
                });

            }
        }.bind(this, field));
    }
});