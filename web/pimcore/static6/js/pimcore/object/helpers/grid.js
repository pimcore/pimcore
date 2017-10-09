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

    getStore: function(noBatchColumns) {

        // the store
        var readerFields = [];
        readerFields.push({name: "id", allowBlank: true});
        readerFields.push({name: "idPath", allowBlank: true});
        readerFields.push({name: "fullpath", allowBlank: true});
        readerFields.push({name: "published", allowBlank: true});
        readerFields.push({name: "type", allowBlank: true});
        readerFields.push({name: "subtype", allowBlank: true});
        readerFields.push({name: "filename", allowBlank: true});
        readerFields.push({name: "classname", allowBlank: true});
        readerFields.push({name: "creationDate", allowBlank: true, type: 'date', dateFormat: 'timestamp'});
        readerFields.push({name: "modificationDate", allowBlank: true, type: 'date', dateFormat: 'timestamp'});
        readerFields.push({name: "inheritedFields", allowBlank: false});
        readerFields.push({name: "metadata", allowBlank: true});
        readerFields.push({name: "#kv-tr", allowBlank: true});

        this.noBatchColumns = [];

        for (var i = 0; i < this.fields.length; i++) {
            if (!in_array(this.fields[i].key, ["creationDate", "modificationDate"])) {

                var fieldConfig = this.fields[i];
                var type = fieldConfig.type;
                var key = fieldConfig.key;
                var readerFieldConfig = {name: key, allowBlank: true};
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
                        }.bind(this, key)
                        var readerFieldConfigOptions = {name: key + "%options", allowBlank: true, persist: false};
                    }

                    readerFields.push(readerFieldConfigOptions);
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
            actionMethods: {
                create : 'POST',
                read   : 'POST',
                update : 'POST',
                destroy: 'POST'
            },
            listeners: {
                exception: function (proxy, request, operation, eOpts) {
                    console.log("exception");
                    if(operation.getAction() == "update") {
                        Ext.MessageBox.alert(t('error'),
                            t('cannot_save_object_please_try_to_edit_the_object_in_detail_view'));
                        this.store.rejectChanges();
                    }
                }.bind(this)
            },
            extraParams: this.baseParams
        };

        var writer = null;
        var listeners = {};
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
            listeners: listeners,
            autoDestroy: true,
            fields: readerFields,
            proxy: proxy,
            autoSync: true
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
                gridColumns.push({header: t("type"), width: this.getColumnWidth(field, 40), sortable: true, dataIndex: 'subtype',
                    hidden: !this.showSubtype,
                    renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                        return '<div style="height: 16px;" class="pimcore_icon_asset  pimcore_icon_'
                        + value + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                    }});
            } else if(field.key == "id") {
                gridColumns.push({header: 'ID', width: this.getColumnWidth(field, this.getColumnWidth(field, 40)), sortable: true,
                    dataIndex: 'id', filter: 'numeric'});
            } else if(field.key == "published") {
                gridColumns.push(new Ext.grid.column.Check({
                    header: t("published"),
                    width: 40,
                    sortable: true,
                    filter: 'boolean',
                    dataIndex: "published"
                }));
            } else if(field.key == "fullpath") {
                gridColumns.push({header: t("path"), width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'fullpath', filter: "string"});
            } else if(field.key == "filename") {
                gridColumns.push({header: t("filename"), width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'filename', hidden: !showKey});
            } else if(field.key == "classname") {
                gridColumns.push({header: t("class"), width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'classname',renderer: function(v){return ts(v);}/*, hidden: true*/});
            } else if(field.key == "creationDate") {
                gridColumns.push({header: t("creationdate") + " (System)", width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: "creationDate", filter: 'date', editable: false, renderer: function(d) {
                        return Ext.Date.format(d, "Y-m-d H:i:s");
                    }/*, hidden: !propertyVisibility.creationDate*/});
            } else if(field.key == "modificationDate") {
                gridColumns.push({header: t("modificationdate") + " (System)", width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: "modificationDate", filter: 'date', editable: false, renderer: function(d) {

                        return Ext.Date.format(d, "Y-m-d H:i:s");
                    }/*, hidden: !propertyVisibility.modificationDate*/});
            } else {
                if (fields[i].isOperator) {
                    gridColumns.push({header: field.attributes.label ? field.attributes.label : field.attributes.key, width: 200, sortable: false,
                        dataIndex: fields[i].key, editable: false});

                } else {
                    var fieldType = fields[i].type;
                    var tag = pimcore.object.tags[fieldType];
                    if (tag) {
                        var fc = tag.prototype.getGridColumnConfig(field);
                        fc.width = this.getColumnWidth(field, 100);

                        if (typeof gridFilters[field.key] !== 'undefined') {
                            fc.filter = gridFilters[field.key];
                        }

                        if (this.isSearch) {
                            fc.sortable = false;
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
    }

});
