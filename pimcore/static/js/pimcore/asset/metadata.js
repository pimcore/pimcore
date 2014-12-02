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
                emptyText: t('key')
            });

            var customType = new Ext.form.ComboBox({
                fieldLabel: t('type'),
                name: "type",
                valueField: "id",
                displayField:'name',
                store: [
                    ["input", t("input")],
                    ["textarea", t("textarea")],
                    ["document", "Document"],
                    ["asset", "Asset"],
                    ["object", "Object"],
                    ["date", "Date"],
                    ["checkbox", "checkbox"]
                ],
                editable: false,
                triggerAction: 'all',
                mode: "local",
                width: 120,
                listWidth: 120,
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
                fieldLabel: t('language'),
                name: "language",
                store: languagestore,
                editable: false,
                triggerAction: 'all',
                mode: "local",
                width: 100,
                listWidth: 100,
                emptyText: t('language')
            });

            var store = new Ext.data.JsonStore({
                fields: ['name', "type", {
                    name: "data",
                    convert: function (v, r) {
                        if (r.type == "date") {
                            var d = new Date(intval(v) * 1000);
                            return d;
                        }
                        return v;
                    }
                }, "language", "config"],
                data: this.asset.data.metadata
            });

            this.grid = new Ext.grid.EditorGridPanel({
                title: t("metadata"),
                autoScroll: true,
                region: "center",
                iconCls: "pimcore_icon_metadata",
                reference: this,
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
                clicksToEdit: 1,
                autoExpandColumn: "value_col",
                columnLines: true,
                stripeRows: true,
                rowupdated: this.updateRows.bind(this, "rowupdated"),
                refresh: this.updateRows.bind(this, "refresh"),
                view: new Ext.grid.GridView({
                    listeners: {
                        rowupdated: this.updateRows.bind(this, "rowupdated"),
                        refresh: this.updateRows.bind(this, "refresh")
                    }
                }),
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
                        header: t("name"),
                        dataIndex: 'name',
                        editor: new Ext.form.TextField({
                            allowBlank: false
                        }),
                        sortable: true,
                        width: 230
                    },
                    {
                        header: t('language'),
                        sortable: true,
                        dataIndex: "language",
                        editor: new Ext.form.ComboBox({
                            name: "language",
                            store: languagestore,
                            editable: false,
                            triggerAction: 'all',
                            mode: "local"
                        }),
                        width: 80
                    },
                    {
                        id: "value_col",
                        header: t("value"),
                        dataIndex: 'data',
                        getCellEditor: this.getCellEditor.bind(this),
                        editable: true,
                        renderer: this.getCellRenderer.bind(this),
                        listeners: {
                            "mousedown": this.cellMousedown.bind(this)
                        }
                    },
                    {
                        xtype: 'actioncolumn',
                        width: 30,
                        items: [{
                            tooltip: t('open'),
                            icon: "/pimcore/static/img/icon/pencil_go.png",
                            handler: function (grid, rowIndex) {
                                var pData = grid.getStore().getAt(rowIndex).data;
                                if (pData.data) {
                                    pimcore.helpers.openElement(pData.data, pData.type);
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
                        width: 30,
                        items: [{
                            tooltip: t('delete'),
                            icon: "/pimcore/static/img/icon/cross.png",
                            handler: function (grid, rowIndex) {
                                grid.getStore().removeAt(rowIndex);
                            }.bind(this)
                        }]
                    }
                ]
            });

            this.grid.on("viewready", this.updateRows.bind(this));
            store.on("update", this.updateRows.bind(this));
        }

        return this.grid;
    },

    updateRows: function (event) {
        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid3-row");

        for (var i = 0; i < rows.length; i++) {

            try {
                var propertyName = Ext.get(rows[i]).query(".x-grid3-cell-first div div")[0].getAttribute("name");
                var storeIndex = this.grid.getStore().findExact("name", propertyName);

                var data = this.grid.getStore().getAt(storeIndex).data;

                if(in_array(data.name, this.disallowedKeys)) {
                    Ext.get(rows[i]).addClass("pimcore_properties_hidden_row");
                }

                if (data.type == "document" || data.type == "asset" || data.type == "object") {

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

                            var rec = this.grid.getStore().getAt(myRowIndex);

                            if(data.node.attributes.elementType != rec.get("type")) {
                                return false;
                            }

                            rec.set("data", data.node.attributes.path);

                            this.updateRows();

                            return true;
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
        return '<div style="background: url(/pimcore/static/img/icon/' + value + '.png) '
            + 'center center no-repeat; height: 16px;" name="' + record.data.name + '">&nbsp;</div>';
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
                return value.format("Y-m-d");
            }
        } else if (type == "checkbox") {
            metaData.css += ' x-grid3-check-col-td';
            return String.format('<div class="x-grid3-check-col{0}" '
            + 'style="background-position:10px center;">&#160;</div>', value ? '-on' : '');
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

        var newRecord = new store.recordType({
            name: key,
            data: value,
            type: type,
            language: language
        });

        store.add(newRecord);
        this.grid.getView().refresh();
    },

    cellMousedown: function (col, grid, rowIndex, event) {

        // this is used for the boolean field type

        var store = grid.getStore();
        var record = store.getAt(rowIndex);
        var data = record.data;
        var type = data.type;

        if (type == "checkbox") {
            record.set("data", !record.data.data);
        }
    },

    getCellEditor: function (rowIndex) {

        var store = this.grid.getStore();
        var data = store.getAt(rowIndex).data;

        var type = data.type;
        var property;

        if (type == "input") {
            property = new Ext.form.TextField();
        } else if (type == "textarea") {
            property = new Ext.form.TextArea();
        } else if (type == "document" || type == "asset" || type == "object") {

            property = new Ext.form.TextField({
                disabled: true,
                propertyGrid: this.grid,
                myRowIndex: rowIndex,
                style: {
                    visibility: "hidden"
                }
            });
        } else if (type == "date") {
            property = new Ext.form.DateField();
        } else if (type == "checkbox") {
            property = new Ext.form.Checkbox();
            return false;
        } else if (type == "select") {
            var config = data.config;
            property = new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: config.split(",")
            });
        }

        return new Ext.grid.GridEditor(property);
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
                var newRecord = new store.recordType({
                    name: key,
                    data: value,
                    type: item.type,
                    config: item.config,
                    language: language
                });

                store.add(newRecord);
                added = true;
            }

        }

        if (added) {
            this.grid.getView().refresh();
        }

    }
});