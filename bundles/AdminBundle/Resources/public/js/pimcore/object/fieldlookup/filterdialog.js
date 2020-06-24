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

pimcore.registerNS("pimcore.object.fieldlookup.filterdialog");
pimcore.object.fieldlookup.filterdialog = Class.create({

    data: {},
    brickKeys: [],


    initialize: function (columnConfig, callback, object) {

        this.config = columnConfig;
        this.callback = callback;
        this.url = "/admin/class/get-class-definition-for-column-config";

        this.object = object;

        if (!this.callback) {
            this.callback = function () {
            };
        }

        this.configPanel = new Ext.Panel({
            layout: "border",
            items: [this.getFilterPanel(), this.getResultPanel(), this.getPreviewPanel()]

        });

        this.window = new Ext.Window({
            width: 850,
            height: 750,
            modal: true,
            split: true,
            title: t('fieldlookup'),
            layout: "fit",
            items: [this.configPanel]
        });
    },

    show: function () {
        this.window.show();
    },

    getPreviewPanel: function () {
        if (!this.previewPanel) {
            this.previewPanel = new Ext.form.FormPanel({
                height: 200,
                autoScroll: true,
                layout: "form",
                split: true,
                region: "south",
                hideLabels: false,
                labelAlign: 'left',   // or 'right' or 'top'
                labelWidth: 100,       // defaults to 100
                labelPad: 8,           // defaults to 5, must specify labelWidth to be honored,
                border: true

            });
        }
        return this.previewPanel;
    },

    getData: function () {
        this.data = {};

        if (this.selectionPanel) {
            this.data.columns = [];
            this.selectionPanel.getRootNode().eachChild(function (child) {
                var obj = {
                    key: child.attributes.key,
                    label: child.attributes.text,
                    type: child.attributes.dataType,
                    layout: child.attributes.layout
                };
                if (child.attributes.width) {
                    obj.width = child.attributes.width;
                }

                this.data.columns.push(obj);
            }.bind(this));
        }

        return this.data;
    },

    getFilterPanel: function () {

        if (!this.filterPanel) {

            var value = null;
            var storedata = [["default", t("default")]];
            for (var i = 0; i < pimcore.settings.websiteLanguages.length; i++) {
                if (i == 0) {
                    value = pimcore.settings.websiteLanguages[0];
                }
                storedata.push([pimcore.settings.websiteLanguages[i],
                    pimcore.available_languages[pimcore.settings.websiteLanguages[i]]]);
            }

            this.languageField = new Ext.form.ComboBox({
                name: "language",
                width: 330,
                mode: 'local',
                autoSelect: true,
                editable: false,
                value: value,
                store: new Ext.data.ArrayStore({
                    id: 0,
                    fields: [
                        'id',
                        'label'
                    ],
                    data: storedata
                }),
                listeners: {
                    change: function () {
                        this.updatePreview();
                    }.bind(this)
                },
                triggerAction: 'all',
                valueField: 'id',
                displayField: 'label'
            });


            this.filterPanel = new Ext.form.FormPanel({
                region: "north",
                bodyStyle: "padding: 5px;",
                height: 40,
                items: [this.languageField]
            });
        }
        return this.filterPanel;
    },

    getResultPanel: function () {
        if (!this.resultPanel) {

            var items = [];

            this.brickKeys = [];
            this.resultPanel = this.getClassTree(this.url, this.config.classid);
        }

        return this.resultPanel;
    },

    getClassTree: function (url, id) {

        var classTreeHelper = new pimcore.object.fieldlookup.helper(true, {}, this.object);
        var tree = classTreeHelper.getClassTree(url, id, this.object.id);

        tree.addListener("itemclick", function (tree, record, item, index, e, eOpts) {
            if (!record.data.root && record.data.type != "layout") {

                this.currentRecord = record;
                this.updatePreview();
            }
        }.bind(this));

        return tree;
    },

    updatePreview: function () {
        var record = this.currentRecord;
        if (!record) {
            return;
        }
        var dataType = record.data.dataType;
        var layout = record.data.layout;

        var language = this.languageField.getValue();
        if (!language && pimcore.settings.websiteLanguages && pimcore.settings.websiteLanguages.length > 0) {
            language = pimcore.settings.websiteLanguages[0];
        }

        var fieldname = record.data.layout.name;
        var objectData = this.object.data.data;
        var dataContext = record.data.dataContext;
        var data;

        if (record.data.dataType == "system") {
            var mapping = {
                id: "o_id",
                key: "o_key",
                published: "o_published",
                creationDate: "o_creationDate",
                modificationDate: "o_modificationDate",
                classname: "o_className",
                filename: "o_key"
            };

            if (mapping[fieldname]) {
                fieldname = mapping[fieldname];
            }

            if (fieldname == "filename") {
                console.log("filename");
            } else {
                data = this.object.data.general[fieldname];
            }
        } else if (dataContext["containerType"] == "objectbricks") {
            var containerKey = dataContext["containerKey"];
            var brickField = dataContext["brickField"];
            if (objectData[brickField]) {
                let bricks = objectData[brickField];
                if (bricks) {
                    for (let i = 0; i < bricks.length; i++) {
                        let brick = bricks[i];
                        if (brick.type == containerKey) {
                            objectData = brick.data;
                            data = objectData[fieldname];
                            break;
                        }
                    }
                }
            }
        } else {
            data = this.object.data.data[fieldname];
        }

        if (typeof data === "undefined") {
            if (objectData.localizedfields
                && objectData.localizedfields.data
                && objectData.localizedfields.data[language] && objectData.localizedfields.data[language][fieldname]) {
                data = objectData.localizedfields.data[language][fieldname];
            }
        }


        if (record.data.dataType == "system") {
            var layout = new Ext.form.TextField({
                fieldLabel: fieldname,
                labelWidth: 100,
                value: data,
                readOnly: true
            });
        } else {
            layout.noteditable = true;
            var tag = new pimcore.object.tags[dataType](data, layout);
            tag.setObject(this.object);
            var layout = tag.getLayoutShow();
        }
        var panel = this.getPreviewPanel();
        panel.removeAll();
        panel.add(layout);
        panel.updateLayout();
    }
});
