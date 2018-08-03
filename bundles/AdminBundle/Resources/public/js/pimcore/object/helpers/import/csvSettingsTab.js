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

    initialize: function (config, callback) {
        this.config = config;
        this.config.csvSettings = this.config.csvSettings || {};
        this.callback = callback;
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

    rebuildPanel: function () {
        this.settingsForm.removeAll(true);

        this.delimiterField = new Ext.form.TextField({
            fieldLabel: t('delimiter'),
            name: 'delimiter',
            value: this.config.csvSettings.delimiter
        });

        this.escapeCharField = new Ext.form.TextField({
            fieldLabel: t('escapechar'),
            name: 'escapechar',
            value: this.config.csvSettings.escapechar
        });

        this.lineTerminatorField = new Ext.form.TextField({
            fieldLabel: t('lineterminator'),
            name: 'lineterminator',
            value: this.config.csvSettings.lineterminator
        });

        this.quoteCharField = new Ext.form.TextField({
            fieldLabel: t('quotechar'),
            name: 'quotechar',
            value: this.config.csvSettings.quotechar
        });

        this.settingsForm.add(
            this.delimiterField,
            this.escapeCharField,
            this.lineTerminatorField,
            this.quoteCharField);
    },

    commitData: function () {
        var settings = this.settingsForm.getValues();
        this.config.csvSettings = settings;
    }


});
