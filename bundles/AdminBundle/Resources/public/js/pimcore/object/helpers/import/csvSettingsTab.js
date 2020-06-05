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

pimcore.registerNS("pimcore.object.helpers.import.csvSettingsTab");
pimcore.object.helpers.import.csvSettingsTab = Class.create({

    initialize: function (config, showReload, callback) {
        this.config = config;
        this.config.csvSettings = this.config.csvSettings || {};
        this.callback = callback;
        this.showReload = (!showReload ? showReload : true);
    },

    getPanel: function () {

        if (!this.csvSettingsForm) {
            this.settingsForm = new Ext.form.FormPanel({
                title: t('csv_settings'),
                iconCls: 'pimcore_icon_file_types',
                defaults: {
                    labelWidth: 150,
                    width: 400
                },
                items: [
                    this.delimiterField,
                    this.escapeCharField,
                    this.lineTerminatorField,
                    this.quoteCharField
                ],
                bodyStyle: "padding: 10px;"

            });
            this.rebuildPanel();
        }
        return this.settingsForm;
    },

    updateColumnConfig: function (isReload, dialect) {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_dataobjecthelper_importgetfileinfo'),
            params: {
                impotConfigId: this.callback.config.importConfigId,
                importId: this.callback.uniqueImportId,
                method: "post",
                className: this.callback.className,
                classId: this.callback.classId,
                dialect: dialect
            },
            success: function (response) {
                var rdata = Ext.decode(response.responseText);
                if (rdata && rdata.success) {
                    Ext.apply(this.callback.config, rdata.config);
                    this.callback.config.resolverSettings = this.callback.config.resolverSettings || {
                        skipHeadRow: true
                    };
                    this.callback.config.shareSettings = this.callback.config.shareSettings || {};
                    this.callback.buildDefaultSelection();
                    this.callback.reloadPanels();
                    this.callback.tabPanel.setActiveTab(this.callback.columnConfigPanel.getPanel());
                }
            }.bind(this)
        });
    },

    rebuildPanel: function () {
        this.settingsForm.removeAll(true);

        this.delimiterField = new Ext.form.TextField({
            fieldLabel: t('delimiter'),
            name: 'delimiter',
            value: this.config.csvSettings.delimiter,
            allowBlank: false,
            blankText: t("this_field_is_required")
        });

        this.escapeCharField = new Ext.form.TextField({
            fieldLabel: t('escapechar'),
            name: 'escapechar',
            value: this.config.csvSettings.escapechar,
            allowBlank: false,
            blankText: t("this_field_is_required")
        });

        this.lineTerminatorField = new Ext.form.TextField({
            fieldLabel: t('lineterminator'),
            name: 'lineterminator',
            value: this.config.csvSettings.lineterminator,
            allowBlank: false,
            blankText: t("this_field_is_required")
        });

        this.quoteCharField = new Ext.form.TextField({
            fieldLabel: t('quotechar'),
            name: 'quotechar',
            value: this.config.csvSettings.quotechar,
            allowBlank: false,
            blankText: t("this_field_is_required")
        });

        this.settingsForm.add(
            this.delimiterField,
            this.escapeCharField,
            this.lineTerminatorField,
            this.quoteCharField);

        if (this.showReload) {
            this.updateColumnButton = Ext.create('Ext.Button', {
                text: t('reload_column_configuration'),
                renderTo: Ext.getBody(),
                handler: function() {
                    if(this.settingsForm.isValid()) {
                        this.commitData();
                        var dialect = Ext.encode(this.config.csvSettings);
                        this.updateColumnConfig(true, dialect);
                    }
                }.bind(this)
            });

            this.updateColumnLabel = Ext.create('Ext.form.Label', {
                text: t('reload_column_configuration_notice'),
                style: {
                    'display':'block',
                    'margin-top':'30px'
                }
            });

            this.settingsForm.add(
                this.updateColumnButton,
                this.updateColumnLabel);
        }
    },

    commitData: function () {
        var settings = this.settingsForm.getValues();
        this.config.csvSettings = settings;
    }


});
