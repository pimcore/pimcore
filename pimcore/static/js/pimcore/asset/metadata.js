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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
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
                    ["textarea", t("textarea")]
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
            for (var i=0; i<pimcore.settings.websiteLanguages.length; i++) {
                languagestore.push([pimcore.settings.websiteLanguages[i],pimcore.settings.websiteLanguages[i]]);
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
                fields: ['name', "type", "data", "language"],
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
                }],
                clicksToEdit: 1,
                autoExpandColumn: "value_col",
                columnLines: true,
                stripeRows: true,
                columns: [
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
                        renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            var data = store.getAt(rowIndex).data;
                            var type = data.type;

                            if (type == "textarea") {
                                return nl2br(value);
                            }

                            return value;
                        }
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

        }

        return this.grid;
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

    getCellEditor: function (rowIndex) {

        var store = this.grid.getStore();
        var data = store.getAt(rowIndex).data;

        var type = data.type;
        var property;

        if (type == "input") {
            property = new Ext.form.TextField();
        } else if (type == "textarea") {
            property = new Ext.form.TextArea();
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
                values.push({
                    data: currentData.data.data,
                    type: currentData.data.type,
                    name: currentData.data.name,
                    language: currentData.data.language
                });
            }
        }


        return values;
    }
});