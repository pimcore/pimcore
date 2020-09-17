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


/**
 * NOTE: This helper-methods are added to the classes pimcore.object.edit, pimcore.object.fieldcollection,
 * pimcore.object.tags.localizedfields
 */

pimcore.registerNS("pimcore.object.helpers.grid");
pimcore.object.helpers.grid = Class.create({

    baseParams: {},
    showSubtype: true,
    showKey: true,
    enableEditor: false,

    initialize: function(selectedClass, fields, url, baseParams, isSearch) {
        this.selectedClass = selectedClass;
        this.fields = fields;
        this.isSearch = isSearch;

        this.url = url;
        if(baseParams) {
            this.baseParams = baseParams;
        } else {
            this.baseParams = {};
        }

        if(!this.baseParams["class"]) {
            this.baseParams["class"] = this.selectedClass;
        }

        var fieldParam = [];
        for(var i = 0; i < fields.length; i++) {
            fieldParam.push(fields[i].key);
        }

        this.baseParams['fields[]'] = fieldParam;
    },

    getStore: function(noBatchColumns, batchAppendColumns, batchRemoveColumns) {

        batchAppendColumns = batchAppendColumns || [];
        batchRemoveColumns = batchRemoveColumns || [];
        // the store
        var readerFields = [];
        readerFields.push({name: "id"});
        readerFields.push({name: "idPath"});
        readerFields.push({name: "fullpath"});
        readerFields.push({name: "published"});
        readerFields.push({name: "type"});
        readerFields.push({name: "subtype"});
        readerFields.push({name: "filename"});
        readerFields.push({name: "classname"});
        readerFields.push({name: "creationDate", type: 'date', dateFormat: 'timestamp'});
        readerFields.push({name: "modificationDate", type: 'date', dateFormat: 'timestamp'});
        readerFields.push({name: "inheritedFields", allowBlank: false});
        readerFields.push({name: "metadata"});
        readerFields.push({name: "#kv-tr"});

        this.noBatchColumns = [];
        this.batchAppendColumns = [];
        this.batchRemoveColumns = [];

        for (var i = 0; i < this.fields.length; i++) {
            if (!in_array(this.fields[i].key, ["creationDate", "modificationDate"])) {

                var fieldConfig = this.fields[i];
                var type = fieldConfig.type;
                var key = fieldConfig.key;
                var readerFieldConfig = {name: key};
                // dynamic select returns data + options on cell level
                if ((type == "select" || type == "multiselect") && fieldConfig.layout.optionsProviderClass) {
                    if (typeof noBatchColumns != "undefined") {
                        if (fieldConfig.layout.dynamicOptions) {
                            noBatchColumns.push(key);
                        }
                    }

                    if (type == "select") {
                        readerFieldConfig["convert"] = function (key, v, rec) {
                            if (v && typeof v.options !== "undefined") {
                                // split it up and store the options in a separate field
                                rec.set(key + "%options", v.options, {convert: false, dirty: false});
                                return v.value;
                            }
                            return v;
                        }.bind(this, key);
                        var readerFieldConfigOptions = {name: key + "%options", persist: false};
                        readerFields.push(readerFieldConfigOptions);
                    }
                }

                if (pimcore.object.tags[type] && pimcore.object.tags[type].prototype.allowBatchAppend) {
                    batchAppendColumns.push(key);
                }
                if (pimcore.object.tags[type] && pimcore.object.tags[type].prototype.allowBatchRemove) {
                    batchRemoveColumns.push(key);
                }

                readerFields.push(readerFieldConfig);
            }
        }

        var glue = '&';
        if(this.url.indexOf('?') === -1) {
            glue = '?';
        }

        var proxy = {
            type: 'ajax',
            url: this.url,
            reader: {
                type: 'json',
                totalProperty: 'total',
                successProperty: 'success',
                rootProperty: 'data'
            },
            api: {
                create  : this.url + glue + "xaction=create",
                read    : this.url + glue  + "xaction=read",
                update  : this.url + glue  + "xaction=update",
                destroy : this.url + glue  + "xaction=destroy"
            },
            batchActions: false,
            actionMethods: {
                create : 'POST',
                read   : 'GET',
                update : 'POST',
                destroy: 'POST'
            },
            listeners: {
                exception: function (proxy, request, operation, eOpts) {
                    if(operation.getAction() == "update") {
                        Ext.MessageBox.alert(t('error'),
                            t('cannot_save_object_please_try_to_edit_the_object_in_detail_view'));
                        this.store.rejectChanges();
                    }
                }.bind(this)
            },
            extraParams: this.baseParams
        };

        if(this.enableEditor) {
            proxy.writer = {
                type: 'json',
                //writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
            };
        }

        this.store = new Ext.data.Store({
            remoteSort: true,
            remoteFilter: true,
            autoDestroy: true,
            fields: readerFields,
            proxy: proxy,
            autoSync: true,
            listeners: {
                "beforeload": function (store) {
                    store.getProxy().abort();
                }
            }
        });

        return this.store;

    },

    selectionColumn: null,
    getSelectionColumn: function() {
        if(this.selectionColumn == null) {
            this.selectionColumn = Ext.create('Ext.selection.CheckboxModel', {});
        }
        return this.selectionColumn;
    },

    getGridColumns: function() {
        // get current class
        var classStore = pimcore.globalmanager.get("object_types_store");
        var klassIndex = classStore.findExact("text", this.selectedClass);
        var klass = classStore.getAt(klassIndex);
        var propertyVisibility = klass.get("propertyVisibility");

        if(this.isSearch) {
            propertyVisibility = propertyVisibility.search;
        } else {
            propertyVisibility = propertyVisibility.grid;
        }
        var showKey = propertyVisibility.path;
        if(this.showKey) {
            showKey = true;
        }

        // init grid-columns
        var gridColumns = [];

        var gridFilters = this.getGridFilters();

        var fields = this.fields;
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];

            if(field.key == "subtype") {
                gridColumns.push({text: t("type"), width: this.getColumnWidth(field, 40), sortable: true, dataIndex: 'subtype',
                    hidden: !this.showSubtype,
                    locked: this.getColumnLock(field),
                    renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                        return '<div style="height: 16px;" class="pimcore_icon_asset  pimcore_icon_'
                        + value + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                    }});
            } else if(field.key == "id") {
                gridColumns.push({text: 'ID', width: this.getColumnWidth(field, this.getColumnWidth(field, 40)), sortable: true,
                    dataIndex: 'id', filter: 'numeric', locked: this.getColumnLock(field)});
            } else if(field.key == "published") {
                gridColumns.push(new Ext.grid.column.Check({
                    text: t("published"),
                    width: 40,
                    sortable: true,
                    filter: 'boolean',
                    dataIndex: "published",
                    disabled: this.isSearch,
                    locked: this.getColumnLock(field)
                }));
            } else if(field.key == "fullpath") {
                gridColumns.push({text: t("path"), width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'fullpath', filter: "string", locked: this.getColumnLock(field)});
            } else if(field.key == "filename") {
                gridColumns.push({text: t("filename"), width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'filename', hidden: !showKey, locked: this.getColumnLock(field)});
            } else if(field.key == "key") {
                gridColumns.push({text: t("key"), width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'key', hidden: !showKey, filter: 'string', locked: this.getColumnLock(field)});
            } else if(field.key == "classname") {
                gridColumns.push({text: t("class"), width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'classname', locked: this.getColumnLock(field), renderer: function(v){return t(v);}/*, hidden: true*/});
            } else if(field.key == "creationDate") {
                gridColumns.push({text: t("creationdate") + " (System)", width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: "creationDate", filter: 'date', editable: false, locked: this.getColumnLock(field), renderer: function(d) {
                        return Ext.Date.format(d, "Y-m-d H:i:s");
                    }/*, hidden: !propertyVisibility.creationDate*/});
            } else if(field.key == "modificationDate") {
                gridColumns.push({text: t("modificationdate") + " (System)", width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: "modificationDate", filter: 'date', editable: false, locked: this.getColumnLock(field), renderer: function(d) {

                        return Ext.Date.format(d, "Y-m-d H:i:s");
                    }/*, hidden: !propertyVisibility.modificationDate*/});
            } else {
                if (fields[i].isOperator) {
                    var operatorColumnConfig = {text: field.attributes.label ? field.attributes.label : field.attributes.key, width: field.width ? field.width : 200, locked: this.getColumnLock(field), sortable: false,
                        dataIndex: fields[i].key, editable: false};

                    if (field.attributes.renderer && pimcore.object.tags[field.attributes.renderer]) {
                        var tag = new pimcore.object.tags[field.attributes.renderer]({}, {});
                        var fc = tag.getGridColumnConfig({
                            key: field.attributes.key
                        });
                        operatorColumnConfig["renderer"] = fc.renderer;
                    }


                    operatorColumnConfig.getEditor = function() {
                        return new pimcore.element.helpers.gridCellEditor({
                            fieldInfo: {
                                layout: {
                                    noteditable: true
                                }
                            }
                        });
                    }.bind(this);

                    gridColumns.push(operatorColumnConfig);

                } else {
                    var fieldType = fields[i].type;
                    var tag = pimcore.object.tags[fieldType];
                    if (tag) {
                        var fc = tag.prototype.getGridColumnConfig(field);
                        fc.width = this.getColumnWidth(field, 100);
                        
                        if (field.layout.decimalPrecision) {
                            fc.decimalPrecision = field.layout.decimalPrecision;
                        }

                        if (typeof gridFilters[field.key] !== 'undefined') {
                            fc.filter = gridFilters[field.key];
                        }

                        if (this.isSearch) {
                            fc.sortable = false;
                        }

                        fc.locked = this.getColumnLock(field);

                        if ((fieldType === "select" || fieldType === "multiselect") && field.layout.options.length > 0) {
                            field.layout.options.forEach(option => {
                                option.key = t(option.key);
                            });
                        }

                        gridColumns.push(fc);
                        gridColumns[gridColumns.length - 1].hidden = false;
                        gridColumns[gridColumns.length - 1].layout = fields[i];
                    } else {
                        console.log("could not resolve field type: " + fieldType);
                    }
                }
            }
        }

        return gridColumns;
    },

    getColumnWidth: function(field, defaultValue) {
        if (field.width) {
            return field.width;
        } else if(field.layout && field.layout.width) {
            return field.layout.width;
        } else {
            return defaultValue;
        }
    },

    getColumnLock: function(field) {
        if (field.locked) {
            return field.locked;
        } else {
            return false;
        }
    },

    getGridFilters: function() {
        var configuredFilters = {
            filter: "string",
            creationDate: "date",
            modificationDate: "date"
        };

        var fields = this.fields;
        for (var i = 0; i < fields.length; i++) {

            if(fields[i].key != "id" && fields[i].key != "published"
                && fields[i].key != "filename" && fields[i].key != "classname"
                && fields[i].key != "creationDate" && fields[i].key != "modificationDate") {

                if (fields[i].key == "fullpath") {
                    configuredFilters.fullpath = {
                        type: "string"
                    };
                } else {
                    if (fields[i].isOperator) {
                        continue;
                    }

                    if (this.isSearch && fields[i].key.startsWith("~classificationstore")) {
                        continue;
                    }

                    var fieldType = fields[i].type;
                    var tag = pimcore.object.tags[fieldType];
                    if (tag) {
                        var filter = tag.prototype.getGridColumnFilter(fields[i]);
                        if (filter) {
                            configuredFilters[filter.dataIndex] = filter;
                        }
                    } else {
                        console.log("could not resolve fieldType: " + fieldType);

                    }
                }
            }

        }


        return configuredFilters;

    },

    applyGridEvents: function(grid) {
        var fields = this.fields;
        for (var i = 0; i < fields.length; i++) {

            if (fields[i].isOperator) {
                continue;
            }

            if(fields[i].key != "id" && fields[i].key != "published" && fields[i].key != "fullpath"
                && fields[i].key != "filename" && fields[i].key != "classname"
                && fields[i].key != "creationDate" && fields[i].key != "modificationDate") {

                var fieldType = fields[i].type;
                var tag = pimcore.object.tags[fieldType];
                if (tag) {
                    tag.prototype.applyGridEvents(grid, fields[i]);
                } else {
                    console.log("could not resolve field type " + fieldType);
                }
            }

        }
    },

    advancedRelationGridRenderer: function (field, pathProperty, value, metaData, record) {
        var key = field.key;
        this.applyPermissionStyle(key, value, metaData, record);

        if(record.data.inheritedFields[key]
            && record.data.inheritedFields[key].inherited == true) {
            metaData.tdCls += " grid_value_inherited";
        }


        if (value && value.length) {
            var result;

            var columnKeys = field.layout.columnKeys ? field.layout.columnKeys : [];
            if (columnKeys && columnKeys.length) {
                result = '<table border="0" cellpadding="0"  cellspacing="0" style="border-collapse: collapse;">';

                result += '<tr><td>&nbsp;</td>';
                for (let i = 0; i < columnKeys.length; i++) {
                    result += '<td style="padding: 0 5px 0 5px; font-size:11px; border-bottom: 1px solid #d0d0d0; border-top: 1px solid #d0d0d0; border-left: 1px solid #d0d0d0; border-right: 1px solid #d0d0d0;">' + t(columnKeys[i]) + '</td>';
                }
                result += '</tr>';


                for (let i = 0; i < value.length && i < 10; i++) {
                    result += '<tr>';

                    result += '<td style="padding: 0 5px 0 5px; border-bottom: 1px solid #d0d0d0;  border-top: 1px solid #d0d0d0; border-left: 1px solid #d0d0d0;">';
                    let item = value[i];
                    result += item[pathProperty];
                    result += '</td>';

                    for (let col = 0; col < columnKeys.length; col++) {
                        let colName = columnKeys[col];
                        result += '<td style="padding: 0 5px 0 5px; font-size:11px; border-bottom: 1px solid #d0d0d0;  border-top: 1px solid #d0d0d0; border-left: 1px solid #d0d0d0; border-right: 1px solid #d0d0d0;">';
                        let displayValue = item[colName] ? item[colName] : "&nbsp";
                        let colType = field.layout.columns[col].type;
                        let colValues = field.layout.columns[col].value;

                        // Replace multiple values
                        if (colType === "multiselect") {
                            colValues.split(";").forEach(value => {
                                if (displayValue.indexOf(value + ",") === 0) {
                                    displayValue = t(value) + displayValue.substr(value.length);
                                } else if (displayValue.indexOf("," + value) === displayValue.length - value.length - 1) {
                                    displayValue = displayValue.substr(0, displayValue.length - value.length) + t(value);
                                } else {
                                    displayValue = displayValue.replace("," + value + ",", "," + t(value) + ",");
                                }
                            });
                        } else if (colType === "select") {
                            displayValue = t(displayValue);
                        }

                        result += displayValue;
                        result += '</td>';
                    }

                    result += '</tr>';
                }

                result += '</table>';
            } else {
                result = [];
                for (let i = 0; i < value.length && i < 10; i++) {
                    var item = value[i];
                    result.push(item[pathProperty]);
                }
                return result.join("<br />");
            }
            return result;
        }
        return value;
    }
});
