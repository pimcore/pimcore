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


/**
 * NOTE: This helper-methods are added to the classes pimcore.object.edit, pimcore.object.fieldcollection,
 * pimcore.object.tags.localizedfields
 */

pimcore.registerNS("pimcore.object.helpers.grid");
pimcore.object.helpers.grid = Class.create({

    limit: 20,
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

        if(!this.baseParams.limit) {
            this.baseParams.limit = this.limit;
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

    getStore: function() {

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

        for (var i = 0; i < this.fields.length; i++) {
            if (!in_array(this.fields[i].key, ["creationDate", "modificationDate"])) {
                readerFields.push({name: this.fields[i].key, allowBlank: true});
            }
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
                create  : this.url + "?xaction=create",
                read    : this.url + "?xaction=read",
                update  : this.url + "?xaction=update",
                destroy : this.url + "?xaction=destroy"
            },
            actionMethods: {
                create : 'GET',
                read   : 'GET',
                update : 'GET',
                destroy: 'GET'
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
                encode: 'true',
                listeners: {
                    exception: function (conn, mode, action, request, response, store) {
                        if(action == "update") {
                            Ext.MessageBox.alert(t('error'),
                                t('cannot_save_object_please_try_to_edit_the_object_in_detail_view'));
                            this.store.rejectChanges();
                        }
                    }.bind(this)
                }
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
                    dataIndex: 'id'/*, hidden: !propertyVisibility.id*/});
            } else if(field.key == "published") {
                gridColumns.push(new Ext.grid.column.Check({
                    header: t("published"),
                    width: 40,
                    sortable: true,
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
                var fc = pimcore.object.tags[field.type].prototype.getGridColumnConfig(field);
                fc.width = this.getColumnWidth(field, 100);

                if (typeof gridFilters[field.key] !== 'undefined') {
                    fc.filter = gridFilters[field.key];
                }

                gridColumns.push(fc);
                gridColumns[gridColumns.length-1].hidden = false;
                gridColumns[gridColumns.length-1].layout = fields[i];
            }
        }

        return gridColumns;
    },

    getColumnWidth: function(field, defaultValue) {
        if (field.width) {
            return field.width;
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
                    var filter = pimcore.object.tags[fields[i].type].prototype.getGridColumnFilter(fields[i]);
                    if (filter) {
                        configuredFilters[filter.dataIndex] = filter;
                    }
                }
            }

        }


        return configuredFilters;

    },

    applyGridEvents: function(grid) {
        var fields = this.fields;
        for (var i = 0; i < fields.length; i++) {

            if(fields[i].key != "id" && fields[i].key != "published" && fields[i].key != "fullpath"
                && fields[i].key != "filename" && fields[i].key != "classname"
                && fields[i].key != "creationDate" && fields[i].key != "modificationDate") {


                pimcore.object.tags[fields[i].type].prototype.applyGridEvents(grid, fields[i]);
            }

        }
    }

});
