/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.asset.metadata");
pimcore.asset.metadata = Class.create({

    initialize: function(asset) {
        this.asset = asset;
    },

    getLayout: function () {

        if (this.grid == null) {

            if(this.asset.data.metadata.length < 1) {
                // default fields
                if(this.asset.data.type == "image") {
                    this.asset.data.metadata.push({
                        name: "title",
                        type: "input",
                        language: "",
                        value: ""
                    });
                    this.asset.data.metadata.push({
                        name: "alt",
                        type: "input",
                        language: "",
                        value: ""
                    });
                    this.asset.data.metadata.push({
                        name: "copyright",
                        type: "input",
                        language: "",
                        value: ""
                    });
                }
            }

            var customKey = new Ext.form.TextField({
                name: 'key',
                emptyText: t('name')
            });

            var customType = new Ext.form.ComboBox({
                name: "type",
                valueField: "id",
                displayField:'name',
                store: [
                    ["input", t("input")],
                    ["textarea", t("textarea")],
                    ["document", t("document")],
                    ["asset", t("asset")],
                    ["object", t("object")],
                    ["date", t("date")],
                    ["checkbox", t("checkbox")]
                ],
                editable: false,
                triggerAction: 'all',
                mode: "local",
                width: 100,
                value: "input",
                emptyText: t('type')
            });

            var languagestore = [["",t("none")]];
            var websiteLanguages = pimcore.settings.websiteLanguages;
            var selectContent = "";
            for (var i=0; i<websiteLanguages.length; i++) {
                selectContent = pimcore.available_languages[websiteLanguages[i]] + " [" + websiteLanguages[i] + "]";
                languagestore.push([websiteLanguages[i], selectContent]);
            }

            var customLanguage = new Ext.form.ComboBox({
                name: "language",
                store: languagestore,
                editable: false,
                triggerAction: 'all',
                mode: "local",
                width: 150,
                emptyText: t('language')
            });

            var modelName = 'pimcore.model.assetmetadata';
            if (!Ext.ClassManager.get(modelName)) {
                Ext.define(modelName, {
                        extend: 'Ext.data.Model',
                        fields: ['name', "type", {
                            name: "data",
                            convert: function (v, r) {
                                if (r.data.type == "date" && v && !(v instanceof Date)) {
                                    var d = new Date(intval(v) * 1000);
                                    return d;
                                }
                                return v;
                            }
                        }, "language", "config"]
                    }
                );
            }


            var store = new Ext.data.Store({
                    model: modelName,
                    data: this.asset.data.metadata
            });

            this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1,
                listeners: {
                    beforeedit: function(editor, context, eOpts) {
                        //need to clear cached editors of cell-editing editor in order to
                        //enable different editors per row
                        editor.editors.each(Ext.destroy, Ext);
                        editor.editors.clear();
                    }
                }
            });


            this.grid = Ext.create('Ext.grid.Panel', {
                title: t("metadata"),
                autoScroll: true,
                region: "center",
                iconCls: "pimcore_icon_metadata",
                bodyCls: "pimcore_editable_grid",
                trackMouseOver: true,
                store: store,
                tbar: [{
                    xtype: "tbtext",
                    text: t('add') + " &nbsp;&nbsp;"
                },customKey, customType, customLanguage, {
                    xtype: "button",
                    handler: this.addSetFromUserDefined.bind(this, customKey, customType, customLanguage),
                    iconCls: "pimcore_icon_add"
                }
                ,{
                    xtype: "tbspacer",
                    width: 20
                },"-",{
                    xtype: "tbspacer",
                    width: 20
                },
                {
                    xtype: "button",
                    text: t('add_predefined_metadata_definitions'),
                    handler: this.addSetFromPredefinedDefined.bind(this),
                    iconCls: "pimcore_icon_add"
                }
                ],
                plugins: [
                    this.cellEditing
                ],
                columnLines: true,
                stripeRows: true,
                columns: {
                    items: [
                        {
                            header: t("type"),
                            dataIndex: 'type',
                            editable: false,
                            width: 40,
                            renderer: this.getTypeRenderer.bind(this),
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
                            header: t('language'),
                            sortable: true,
                            dataIndex: "language",
                            getEditor: function() {
                                return new Ext.form.ComboBox({
                                    name: "language",
                                    store: languagestore,
                                    editable: false,
                                    listConfig: {minWidth: 200},
                                    triggerAction: 'all',
                                    mode: "local"
                                });
                            },
                            width: 80
                        },
                        {
                            //id: "value_col",
                            header: t("value"),
                            dataIndex: 'data',
                            getEditor: this.getCellEditor.bind(this),
                            editable: true,
                            renderer: this.getCellRenderer.bind(this),
                            listeners: {
                                "mousedown": this.cellMousedown.bind(this)
                            },
                            flex: 1
                        },
                        {
                            xtype: 'actioncolumn',
                            width: 40,
                            items: [{
                                tooltip: t('open'),
                                icon: "/pimcore/static6/img/flat-color-icons/cursor.svg",
                                handler: function (grid, rowIndex) {
                                    var pData = grid.getStore().getAt(rowIndex).data;
                                    if (pData.data) {
                                        pimcore.helpers.openElement(pData.data, pData.type);
                                    }
                                }.bind(this),
                                getClass: function (v, meta, rec) {  // Or return a class from a function
                                    if (rec.get('type') != "object" && rec.get('type') != "document"
                                        && rec.get('type') != "asset") {
                                        return "pimcore_hidden";
                                    }
                                }
                            }
                            ]
                        },
                        {
                            xtype: 'actioncolumn',
                            width: 40,
                            items: [{
                                tooltip: t('delete'),
                                icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
                                handler: function (grid, rowIndex) {
                                    grid.getStore().removeAt(rowIndex);
                                }.bind(this)
                            }]
                        }
                    ]
                }

            });

            this.grid.getView().on("refresh", this.updateRows.bind(this, "view-refresh"));
        }

        return this.grid;
    },

    updateRows: function (event) {
        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid-row");

        for (var i = 0; i < rows.length; i++) {

            try {
                var propertyName = Ext.get(rows[i]).query(".x-grid-cell-first div div")[0].getAttribute("name");
                var storeIndex = this.grid.getStore().findExact("name", propertyName);

                var data = this.grid.getStore().getAt(storeIndex).data;

                if(in_array(data.name, this.disallowedKeys)) {
                    Ext.get(rows[i]).addCls("pimcore_properties_hidden_row");
                }

                if (data.type == "document" || data.type == "asset" || data.type == "object") {

                    // add dnd support
                    var dd = new Ext.dd.DropZone(rows[i], {
                        ddGroup: "element",

                        getTargetFromEvent: function(e) {
                            return this.getEl();
                        },

                        onNodeOver : function(dataRow, node, dragZone, e, data ) {

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

                                var myRecord = this.grid.getStore().getAt(myRowIndex);

                                if (data.elementType != myRecord.get("type")) {
                                    return false;
                                }

                                myRecord.set("data", data.path);

                                this.updateRows();

                                return true;
                            } catch (e) {
                                console.log(e);
                            }
                        }.bind(this, storeIndex)
                    });

                }
            }
            catch (e) {
                console.log(e);
            }
        }
    },



    getTypeRenderer: function (value, metaData, record, rowIndex, colIndex, store) {

        if (value == "input") {
            value = "text";
        }
        return '<div class="pimcore_icon_' + value + ' pimcore_property_grid_type_column" name="' + record.data.name + '">&nbsp;</div>';
    },


    getCellRenderer: function (value, metaData, record, rowIndex, colIndex, store) {

        var data = store.getAt(rowIndex).data;
        var type = data.type;

        if (type == "textarea") {
            return nl2br(value);
        } else if (type == "document" || type == "asset" || type == "object") {
            if (value) {
                return '<div class="pimcore_property_droptarget">' + value + '</div>';
            } else {
                return '<div class="pimcore_property_droptarget">&nbsp;</div>';
            }
        } else if (type == "date") {
            if (value) {
                if(!(value instanceof Date)) {
                    value = new Date(value * 1000);
                }
                return Ext.Date.format(value, "Y-m-d");
            }
        } else if (type == "checkbox") {
            if (value) {
                return '<div style="text-align: left"><div role="button" class="x-grid-checkcolumn x-grid-checkcolumn-checked" style=""></div></div>';
            } else {
                return '<div style="text-align: left"><div role="button" class="x-grid-checkcolumn" style=""></div></div>';
            }
        }

        return value;
    },



    addSetFromUserDefined: function (customKey, customType, customLanguage) {
        this.add(customKey.getValue(), customType.getValue(), false, customLanguage.getValue());
    },

    add: function (key, type, value, language) {

        var store = this.grid.getStore();

        // check for empty key & type
        if (key.length < 2 || type.length < 1) {
            Ext.MessageBox.alert(t("error"), t("name_and_key_must_be_defined"));
            return;
        }

        if (!value) {
            if (type == "input" || type == "textarea") {
                value = "";
            }
            value = "";
        }

        if(!language) {
            language = "";
        }

        // check for duplicate name
        var dublicateIndex = store.findBy(function (record, id) {
            if (record.data.name.toLowerCase() == key.toLowerCase()) {
                if(record.data.language.toLowerCase() == language.toLowerCase()) {
                    return true;
                }
            }
            return false;
        });

        if (dublicateIndex >= 0) {
            Ext.MessageBox.alert(t("error"), t("name_already_in_use"));
            return;
        }

        var model = store.getModel();
        var newRecord = new model({
            name: key,
            data: value,
            type: type,
            language: language
        });

        store.add(newRecord);
        this.grid.getView().refresh();
    },

    cellMousedown: function (grid, cell, rowIndex, cellIndex, e) {

        // this is used for the boolean field type

        var store = grid.getStore();
        var record = store.getAt(rowIndex);
        var data = record.data;
        var type = data.type;

        if (type == "checkbox") {
            record.set("data", !record.data.data);
        }
    },

    getCellEditor: function (record, defaultField ) {
        var data = record.data;

        var type = data.type;
        var property;

        if (type == "input") {
            property = Ext.create('Ext.form.TextField');
        } else if (type == "textarea") {
            property = Ext.create('Ext.form.TextArea');
        } else if (type == "document" || type == "asset" || type == "object") {
            //no editor needed here
        } else if (type == "date") {
            property = Ext.create('Ext.form.field.Date', {
                format: "Y-m-d"
            });
        } else if (type == "checkbox") {
            //no editor needed here
        } else if (type == "select") {
            var config = data.config;
            property =  Ext.create('Ext.form.ComboBox', {
                triggerAction: 'all',
                editable: false,
                store: config.split(",")
            });
        }

        return property;
    },

    getValues : function () {

        if (!this.grid.rendered) {
            throw "metadata not available";
        }

        var values = [];
        var store = this.grid.getStore();
        store.commitChanges();

        var records = store.getRange();

        for (var i = 0; i < records.length; i++) {
            var currentData = records[i];
            if (currentData) {
                var data = currentData.data.data;
                if (data && currentData.data.type == "date") {
                    data = data.valueOf() / 1000;
                }
                values.push({
                    data: data,
                    type: currentData.data.type,
                    name: currentData.data.name,
                    language: currentData.data.language
                });
            }
        }


        return values;
    },

    addSetFromPredefinedDefined: function() {

        Ext.Ajax.request({
            url: "/admin/settings/get-predefined-metadata",
            params: {
                type: "asset",
                subType: this.asset.type
            },
            success: this.doAddSet.bind(this)

        });

    },

    doAddSet: function(response) {
        var data = Ext.decode(response.responseText);
        data = data.data;
        var store = this.grid.getStore();
        var added = false;

        var i;
        for (i = 0; i < data.length; i++) {
            var item = data[i];
            var key = item.name;
            var language = item.language;
            if (!key) {
                key = "";
            }
            if (!language) {
                language = "";
            }

            if (!item.type){
                continue;
            }

            var dublicateIndex = store.findBy(function (record, id) {
                if (record.data.name.toLowerCase() == key.toLowerCase()) {
                    if(record.data.language.toLowerCase() == language.toLowerCase()) {
                        return true;
                    }
                }
                return false;
            });

            if (dublicateIndex < 0) {

                var value = item.data;
                if (item.type == "date" && value) {
                    value = new Date(intval(value) * 1000);
                }
                var newRecord = {
                    name: key,
                    data: value,
                    type: item.type,
                    config: item.config,
                    language: language
                };

                store.add(newRecord);
                added = true;
            }

        }

        if (added) {
            this.grid.getView().refresh();
        }

    }
});