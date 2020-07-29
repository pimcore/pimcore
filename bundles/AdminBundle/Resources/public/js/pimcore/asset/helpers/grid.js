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

pimcore.registerNS("pimcore.asset.helpers.grid");
pimcore.asset.helpers.grid = Class.create({

    baseParams: {},
    enableEditor: false,

    initialize: function(fields, url, baseParams) {
        this.fields = fields;

        this.url = url;
        if(baseParams) {
            this.baseParams = baseParams;
        } else {
            this.baseParams = {};
        }

        var fieldParam = [];
        for(var i = 0; i < fields.length; i++) {
            fieldParam.push(fields[i].key);
        }

        this.baseParams['fields[]'] = fieldParam;
    },

    getStore: function(noBatchColumns, batchAppendColumns) {

        batchAppendColumns = batchAppendColumns || [];
        // the store
        var readerFields = [];
        readerFields.push({name: "preview"});
        readerFields.push({name: "id"});
        readerFields.push({name: "idPath"});
        readerFields.push({name: "fullpath"});
        readerFields.push({name: "type"});
        readerFields.push({name: "subtype"});
        readerFields.push({name: "filename"});
        readerFields.push({name: "classname"});
        readerFields.push({name: "creationDate", type: 'date', dateFormat: 'timestamp'});
        readerFields.push({name: "modificationDate", type: 'date', dateFormat: 'timestamp'});
        readerFields.push({name: "size"});

        this.noBatchColumns = [];
        this.batchAppendColumns = [];

        for (var i = 0; i < this.fields.length; i++) {
            if (!in_array(this.fields[i].key, ["creationDate", "modificationDate"])) {

                var fieldConfig = this.fields[i];
                var type = fieldConfig.type;
                var key = fieldConfig.key;
                var readerFieldConfig = {name: key};
                // dynamic select returns data + options on cell level

                if (pimcore.asset.metadata.tags[type]
                    && typeof pimcore.asset.metadata.tags[type].prototype.addGridOptionsFromColumnConfig == "function") {

                    readerFieldConfig["convert"] = pimcore.asset.metadata.tags[type].prototype.addGridOptionsFromColumnConfig.bind(this, key);

                    var readerFieldConfigOptions = {name: key + "%options", persist: false};
                    readerFields.push(readerFieldConfigOptions);
                }

                if (pimcore.object.tags[type] && pimcore.object.tags[type].prototype.allowBatchAppend) {
                    batchAppendColumns.push(key);
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
                            t('cannot_save_metadata_please_try_to_edit_the_metadata_in_asset'));
                        this.store.rejectChanges();
                    }
                }.bind(this),
            },
            sync:  function(options) {
                this.store.getProxy().setExtraParam("data", this.getValues());
            }.bind(this),
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
            autoLoad: true,
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
        var fields = this.fields;
        var gridColumns = [];

        for (i = 0; i < fields.length; i++) {
            var field = fields[i];
            var key = field.key;
            var language = field.language;
            if (!key) {
                key = "";
            }
            if (!language) {
                language = "";
            }

            if (!field.type) {
                continue;
            }

            if(key.indexOf("~") >= 0 ) {
                key = key.substr(0, key.lastIndexOf('~'));
            }

            if (field.type == "system") {
                if (key == "preview") {
                    gridColumns.push({
                        text: t(field.label),
                        sortable: false,
                        dataIndex: field.key,
                        editable: false,
                        width: this.getColumnWidth(field, 150),
                        locked: this.getColumnLock(field),
                        renderer: function (value) {
                            if (value) {
                                return '<div class="thumb-wrap">',
                                '<div class="thumb"><table cellspacing="0" cellpadding="0" border="0"><tr><td class="thumb-item" align="center" '
                                + 'valign="middle" style="background: url(' + value + ') center center no-repeat; ' +
                                'background-size: contain; width: 200px; height: 100px; cursor: default !important;">'
                                + '</td></tr></table></div></div>';
                            }
                        }.bind(this)
                    });
                } else if (key == "creationDate" || key == "modificationDate") {
                    gridColumns.push({
                        text: t(field.label),
                        width: this.getColumnWidth(field, 150),
                        sortable: true,
                        dataIndex: field.key,
                        editable: false,
                        filter: 'date',
                        locked: this.getColumnLock(field),
                        renderer: function (d) {
                            var date = new Date(d * 1000);
                            return Ext.Date.format(date, "Y-m-d H:i:s");
                        }
                    });
                } else if (key == "filename") {
                    gridColumns.push({
                        text: t(field.label), sortable: true, dataIndex: field.key, editable: false,
                        width: this.getColumnWidth(field, 250), locked: this.getColumnLock(field), filter: 'string', renderer: Ext.util.Format.htmlEncode
                    });
                } else if (key == "fullpath") {
                    gridColumns.push({
                        text: t(field.label), sortable: true, dataIndex: field.key, editable: false,
                        width: this.getColumnWidth(field, 400), locked: this.getColumnLock(field), filter: 'string', renderer: Ext.util.Format.htmlEncode
                    });
                } else if (key == "size") {
                    gridColumns.push({
                        text: t(field.label), sortable: false, dataIndex: field.key, editable: false,
                        width: this.getColumnWidth(field, 130), locked: this.getColumnLock(field)
                    });
                } else {
                    gridColumns.push({
                        text: t(field.label), width: this.getColumnWidth(field, 130), locked: this.getColumnLock(field), sortable: true,
                        dataIndex: field.key
                    });
                }
            } else {
                var fieldType = field.type;
                if (fieldType == "document" || fieldType == "asset" || fieldType == "object") {
                    fieldType = 'manyToOneRelation';
                }

                var tag = pimcore.asset.metadata.tags[fieldType];
                var fc = tag.prototype.getGridColumnConfig(field);
                fc.locked = this.getColumnLock(field);
                gridColumns.push(fc);
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
    }

});