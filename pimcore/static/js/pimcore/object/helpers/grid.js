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


/**
 * NOTE: This helper-methods are added to the classes pimcore.object.edit, pimcore.object.fieldcollection, pimcore.object.tags.localizedfields
 */

pimcore.registerNS("pimcore.object.helpers.grid");
pimcore.object.helpers.grid = Class.create({

    limit: 15,
    baseParams: {},
    showSubtype: true,
    showKey: true,

    initialize: function(selectedClass, fields, url, baseParams) {
        this.selectedClass = selectedClass;
        this.fields = fields;
        this.validFieldTypes = ["textarea","input","checkbox","select","numeric","wysiwyg","image","geopoint","country","href","multihref","objects","language","table","date","datetime","link","multiselect","password","slider","user"];

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
    },

    getStore: function() {

        // the store
        var readerFields = [];
        readerFields.push({name: "id", allowBlank: true});
        readerFields.push({name: "fullpath", allowBlank: true});
        readerFields.push({name: "published", allowBlank: true});
        readerFields.push({name: "type", allowBlank: true});
        readerFields.push({name: "subtype", allowBlank: true});
        readerFields.push({name: "filename", allowBlank: true});
        readerFields.push({name: "classname", allowBlank: true});
        readerFields.push({name: "creationDate", allowBlank: true});
        readerFields.push({name: "modificationDate", allowBlank: true});
        readerFields.push({name: "inheritedFields", allowBlank: false});

        for (var i = 0; i < this.fields.length; i++) {
            readerFields.push({name: this.fields[i].key, allowBlank: true});
        }

        var proxy = new Ext.data.HttpProxy({
            url: this.url
        });
        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            successProperty: 'success',
            root: 'data'
        }, readerFields);


        store = new Ext.data.Store({
            restful: false,
            idProperty: 'id',
            remoteSort: true,
            proxy: proxy,
            reader: reader,
            baseParams: this.baseParams
        });

        return store;

    },

    getGridColumns: function() {
        // get current class
        var classStore = pimcore.globalmanager.get("object_types_store");
        var klassIndex = classStore.findExact("text", this.selectedClass);
        var klass = classStore.getAt(klassIndex);
        var propertyVisibility = klass.get("propertyVisibility");


        var showKey = propertyVisibility.search.path;
        if(this.showKey) {
            showKey = true;
        }

        // init grid-columns
        var gridColumns = [
            {header: t("type"), width: 40, sortable: true, dataIndex: 'subtype', hidden: !this.showSubtype, renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                return '<div style="height: 16px;" class="pimcore_icon_asset  pimcore_icon_' + value + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
            }},
            {header: 'ID', width: 40, sortable: true, dataIndex: 'id', hidden: !propertyVisibility.search.id},
            {header: t("published"), width: 40, sortable: true, dataIndex: 'published', hidden: !propertyVisibility.search.published},
            {header: t("path"), width: 200, sortable: true, dataIndex: 'fullpath', hidden: !propertyVisibility.search.path},
            {header: t("filename"), width: 200, sortable: true, dataIndex: 'filename', hidden: !showKey},
            {header: t("class"), width: 200, sortable: true, dataIndex: 'classname',renderer: function(v){return ts(v);}, hidden: true},
            {header: t("creationdate") + " (System)", width: 200, sortable: true, dataIndex: "creationDate", editable: false, renderer: function(d) {
                var date = new Date(d * 1000);
                return date.format("Y-m-d H:i:s");
            }, hidden: !propertyVisibility.search.creationDate},
            {header: t("modificationdate") + " (System)", width: 200, sortable: true, dataIndex: "modificationDate", editable: false, renderer: function(d) {
                var date = new Date(d * 1000);
                return date.format("Y-m-d H:i:s");
            }, hidden: !propertyVisibility.search.modificationDate}
        ];


        var fields = this.fields;
        for (var i = 0; i < fields.length; i++) {
            if (in_array(fields[i].type, this.validFieldTypes)) {

                var cm = null;
                var store = null;

                var defaultRenderer = function(key, value, metaData, record) {
                    if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                        metaData.css += " grid_value_inherited";
                    }
                    return value;

                }.bind(this, fields[i].key);

                // DATE
                if (fields[i].type == "date") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }

                        if (value) {
                            var timestamp = intval(value) * 1000;
                            var date = new Date(timestamp);

                            return date.format("Y-m-d");
                        }
                        return "";
                    }.bind(this, fields[i].key)});
                }
                // DATETIME
                else if (fields[i].type == "datetime") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }

                        if (value) {
                            var timestamp = intval(value) * 1000;
                            var date = new Date(timestamp);

                            return date.format("Y-m-d H:i");
                        }
                        return "";
                    }.bind(this, fields[i].key)});
                }
                // IMAGE
                else if (fields[i].type == "image") {
                    gridColumns.push({header: ts(fields[i].label), width: 100, sortable: false, dataIndex: fields[i].key, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }

                        if (value && value.id) {
                            return '<img src="/admin/asset/get-image-thumbnail/id/' + value.id + '/width/88/aspectratio/true" />';
                        }
                    }.bind(this, fields[i].key)});
                }
                // GEOPOINT
                else if (fields[i].type == "geopoint") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (key, value, metaData, record) {

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
                }
                // HREF
                else if (fields[i].type == "href") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: defaultRenderer});
                }
                // MULTIHREF & OBJECTS
                else if (fields[i].type == "multihref" || fields[i].type == "objects") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }

                        if (value.length > 0) {
                            return value.join("<br />");
                        }
                    }.bind(this, fields[i].key)});
                }
                // PASSWORD
                else if (fields[i].type == "password") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }

                        return "**********";
                    }.bind(this, fields[i].key)});
                }
                // LINK
                else if (fields[i].type == "link") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: defaultRenderer});
                }
                // MULTISELECT
                else if (fields[i].type == "multiselect") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (key, value, metaData, record) {
                        if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                            metaData.css += " grid_value_inherited";
                        }

                        if (value.length > 0) {
                            return value.join(",");
                        }
                    }.bind(this, fields[i].key)});
                }
                // TABLE
                else if (fields[i].type == "table") {
                    gridColumns.push({header: ts(fields[i].label), width: 150, sortable: false, dataIndex: fields[i].key, renderer: function (key, value, metaData, record) {
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
                }
                // DEFAULT
                else {
                    gridColumns.push({header: ts(fields[i].label), sortable: true, dataIndex: fields[i].key, renderer: defaultRenderer});
                }

                // is visible or not
                gridColumns[gridColumns.length-1].hidden = !fields[i].visibleSearch;

            }
        }


        return gridColumns;
    },


    getGridFilters: function() {
        // filters
        // add filters
        var selectFilterFields;
        var configuredFilters = [];

        var fields = this.fields;
        for (var i = 0; i < fields.length; i++) {
            if (in_array(fields[i].type, this.validFieldTypes)) {
                store = null;
                selectFilterFields = null;

                if (fields[i].type == "input" || fields[i].type == "textarea" || fields[i].type == "wysiwyg") {
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
        var gridfilters = new Ext.ux.grid.GridFilters({
            encode: true,
            local: false,
            filters: configuredFilters
        });

        return gridfilters;

    }
});
