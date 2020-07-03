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

pimcore.registerNS("pimcore.object.helpers.import.resolverSettingsTab");
pimcore.object.helpers.import.resolverSettingsTab = Class.create({

    initialize: function (config, callback) {
        this.config = config;
        this.config.resolverSettings = this.config.resolverSettings || {
            skipHeadRow: true
        };
        this.callback = callback;
    },

    getMappingStore: function () {

        var dataPreview = this.config.dataPreview;
        if (dataPreview) {
            dataPreview = dataPreview[0];
        }

        var data = this.config;
        var sourceFields = [];
        var i;
        for (i = 0; i < data.cols; i++) {
            var text = t("field") + " " + i;
            if (dataPreview && dataPreview["field_" + i]) {
                text = text  + " - " + dataPreview["field_" + i];
            }
            sourceFields.push([i, text]);
        }

        var filenameMappingStore = sourceFields;
        return filenameMappingStore;
    },

    getPanel: function () {

        if (!this.settingsForm) {

            this.settingsForm = new Ext.form.FormPanel({
                title: t('resolver_settings'),
                iconCls: 'pimcore_icon_settings',
                defaults: {
                    labelWidth: 150,
                    width: 400
                },
                items: [],
                bodyStyle: "padding: 10px;"
            });
            this.rebuildPanel();

        }
        return this.settingsForm;
    },

    rebuildPanel: function () {
        this.settingsForm.removeAll(true);

        this.detailedSettingsPanel = new Ext.panel.Panel({
            width: '100%',
            defaults: {
                labelWidth: 200,
                width: 400
            }
        });

        var storedata = [["default", t("default")]];
        for (var i = 0; i < pimcore.settings.websiteLanguages.length; i++) {
            storedata.push([pimcore.settings.websiteLanguages[i],
                pimcore.available_languages[pimcore.settings.websiteLanguages[i]]]);
        }

        this.languageField = new Ext.form.ComboBox({
            name: "language",
            mode: 'local',
            autoSelect: true,
            editable: false,
            fieldLabel: t("language"),
            value: this.config.resolverSettings.language,
            store: new Ext.data.ArrayStore({
                fields: [
                    'id',
                    'label'
                ],
                data: storedata
            }),
            triggerAction: 'all',
            valueField: 'id',
            displayField: 'label'
        });

        var resolverOptions = [];
        resolverOptions.push(["id", t("id")]);
        resolverOptions.push(["filename", t("filename")]);
        resolverOptions.push(["fullpath", t("fullpath")]);
        resolverOptions.push(["code", t("code")]);
        resolverOptions.push(["getBy", t("get_by_resolver")]);

        var resolverStore = new Ext.data.ArrayStore({
            data: resolverOptions,
            fields: ['type', 'name']
        });

        this.resolverCombo = new Ext.form.field.ComboBox(
            {
                name: "strategy",
                store: resolverStore,
                mode: "local",
                triggerAction: "all",
                fieldLabel: t("resolver_strategy"),
                value: this.config.resolverSettings.strategy ? this.config.resolverSettings.strategy : 'id',
                valueField: 'type',
                displayField: 'name',
                listeners: {
                    change: function () {
                        this.rebuildDetailedSettingsPanel();
                    }.bind(this)
                }
            }
        );


        this.skipHeaderRow = new Ext.form.field.Checkbox(
            {
                fieldLabel: t("skipheadrow"),
                inputValue: true,
                name: "skipHeadRow",
                value: this.config.resolverSettings.skipHeadRow,
                listeners: {
                    change: function (checkbox, checked) {
                        this.callback.csvPreviewPanel.setFirstLineHeadline(checked);
                        this.config.resolverSettings.skipHeadRow = checked;
                    }.bind(this)
                },
            }
        );

        this.settingsForm.add(
            this.skipHeaderRow,
            this.languageField,
            this.resolverCombo,
            this.detailedSettingsPanel);

        this.rebuildDetailedSettingsPanel();

    },

    rebuildDetailedSettingsPanel: function () {
        var resolver = this.resolverCombo.getValue();

        this.detailedSettingsPanel.removeAll(true);

        var mappingStore = this.getMappingStore();

        this.detailedSettingsPanel.add(
            {
                xtype: "combo",
                name: "column",
                store: mappingStore,
                mode: "local",
                triggerAction: "all",
                fieldLabel: t("column"),
                width: 600,
                value: this.config.resolverSettings.column ? this.config.resolverSettings.column : 0
            });

        switch (resolver) {
            case "id":
                this.addIdOptions();
                break;
            case "filename":
                this.addFilenameOptions();
                break;
            case "code":
                this.addCodeOptions();
            case "fullpath":
                this.addFullpathOptions();
                break;
            case "getBy":
                this.addGetByOptions();
                break;

        }
    },

    setSkipHeaderRow: function (value) {
        this.skipHeaderRow.setValue(value);
    },

    addObjectTypeOptions: function() {
        var typesData = [];

        typesData.push(["keep", t('keep')]);
        typesData.push(["object", t('object')]);
        typesData.push(["variant", t('variant')]);
        typesData.push(["dynamic", t('dynamic')]);

        var typesStore = new Ext.data.ArrayStore({
            data: typesData,
            fields: ['type', 'name']
        });

        this.objectTypeOptions = new Ext.form.field.ComboBox(
            {
                name: "objectType",
                store: typesStore,
                mode: "local",
                triggerAction: "all",
                fieldLabel: t("type"),
                value: this.config.resolverSettings.objectType ? this.config.resolverSettings.objectType : 'keep',
                valueField: 'type',
                displayField: 'name',
                listeners: {
                    change: function(field, newValue, oldValue) {
                        if (newValue == "dynamic") {
                            this.objectTypeColumn.enable();
                        } else {
                            this.objectTypeColumn.disable();
                        }
                    }.bind(this)
                }
            }
        );

        var mappingStore = this.getMappingStore();

        this.objectTypeColumn = new Ext.form.field.ComboBox(
            {
                xtype: "combo",
                name: "columnObjectType",
                store: mappingStore,
                mode: "local",
                triggerAction: "all",
                fieldLabel: t("type_column"),
                width: 600,
                disabled: this.objectTypeOptions.getValue() != "dynamic",
                value: this.config.resolverSettings.columnObjectType ? this.config.resolverSettings.columnObjectType : 0
            })

        this.detailedSettingsPanel.add(this.objectTypeOptions);
        this.detailedSettingsPanel.add(this.objectTypeColumn);


    },

    addIdOptions: function () {
        this.addObjectTypeOptions();

    },

    addCodeOptions: function () {

        this.detailedSettingsPanel.add([
                {
                    xtype: "textfield",
                    name: "phpClass",
                    fieldLabel: t("php_class"),
                    width: 800,
                    value: this.config.resolverSettings.phpClass
                },
                {
                    xtype: "textfield",
                    name: "params",
                    fieldLabel: t("additional_data"),
                    value: this.config.resolverSettings.params
                }
            ]
        );
    },

    addFilenameOptions: function () {

        this.addObjectTypeOptions();

        this.detailedSettingsPanel.add([
                {
                    xtype: 'displayfield',
                    value: t("object_import_filename_description"),
                    cls: 'pimcore_extra_label_bottom',
                    width: '100%'
                },
                {
                    xtype: "checkbox",
                    name: "overwrite",
                    inputValue: true,
                    value: this.config.resolverSettings.overwrite,
                    fieldLabel: t("overwrite_object_with_same_key")
                },
                {
                    xtype: 'displayfield',
                    value: t("overwrite_object_with_same_key_description"),
                    cls: 'pimcore_extra_label_bottom',
                    width: '100%'
                },
                {
                    xtype: "textfield",
                    name: "prefix",
                    fieldLabel: t("import_file_prefix"),
                    value: this.config.resolverSettings.prefix
                },
                {
                    xtype: "checkbox",
                    name: "skipIfExists",
                    fieldLabel: t("skip_if_exists"),
                    value: this.config.resolverSettings.skipIfExists
                },
                {
                    xtype: "textfield",
                    name: "phpClassOrService",
                    fieldLabel: t("php_class_or_service"),
                    width: 800,
                    value: this.config.resolverSettings.phpClassOrService
                }
            ]
        );
    },

    addFullpathOptions: function () {


        this.addObjectTypeOptions();

        this.detailedSettingsPanel.add([
                {
                    xtype: "checkbox",
                    name: "createOnDemand",
                    inputValue: true,
                    value: this.config.resolverSettings.createOnDemand,
                    fieldLabel: t("create_on_demand")
                },
                {
                    xtype: "checkbox",
                    name: "createParents",
                    inputValue: true,
                    value: this.config.resolverSettings.createParents,
                    fieldLabel: t("create_parents")
                },
                {
                    xtype: "checkbox",
                    name: "skipIfExists",
                    fieldLabel: t("skip_if_exists"),
                    value: this.config.resolverSettings.skipIfExists
                },
                {
                    xtype: "textfield",
                    name: "phpClassOrService",
                    fieldLabel: t("php_class_or_service"),
                    width: 800,
                    value: this.config.resolverSettings.phpClassOrService
                }
            ]
        );
    },

    addGetByOptions: function () {

        this.addObjectTypeOptions();

        this.detailedSettingsPanel.add([
            {
                xtype: "textfield",
                name: "attribute",
                fieldLabel: t("attribute"),
                value: this.config.resolverSettings.attribute
            },
            {
                xtype: "checkbox",
                name: "skipIfExists",
                fieldLabel: t("skip_if_exists"),
                value: this.config.resolverSettings.skipIfExists
            },
            {
                xtype: "checkbox",
                name: "createOnDemand",
                fieldLabel: t("create_on_demand"),
                value: this.config.resolverSettings.createOnDemand
            },
            {
                xtype: "textfield",
                name: "phpClassOrService",
                fieldLabel: t("php_class_or_service"),
                width: 800,
                value: this.config.resolverSettings.phpClassOrService
            }
            ]
        );
    },

    commitData: function () {
        var settings = this.settingsForm.getValues();
        this.config.resolverSettings = settings;
    }

});
