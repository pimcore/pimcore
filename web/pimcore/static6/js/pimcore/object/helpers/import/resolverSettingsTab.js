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
        this.config.resolverSettings = this.config.resolverSettings || {};
        this.callback = callback;
    },

    getMappingStore: function () {
        var data = this.config;
        var sourceFields = [];
        for (i = 0; i < data.cols - 1; i++) {
            sourceFields.push([i, t("field") + " " + i]);
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
                labelWidth: 150,
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
                id: 0,
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
        resolverOptions.push(["code", t("code")]);

        var resolverStore = new Ext.data.ArrayStore({
            data: resolverOptions,
            // sorters: 'name',
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
                readOnly: true,
                fieldLabel: t("skipheadrow"),
                inputValue: true,
                name: "skipHeadRow",
                value: this.config.resolverSettings.skipHeadRow
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
        }
    },

    setSkipHeaderRow: function (value) {
        this.skipHeaderRow.setValue(value);
    },

    addIdOptions: function () {

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
                }
            ]
        );
    },

    commitData: function () {
        var settings = this.settingsForm.getValues();
        this.config.resolverSettings = settings;
    }


});
