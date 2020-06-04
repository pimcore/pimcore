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

pimcore.registerNS("pimcore.object.gridexport.csv");
pimcore.object.gridexport.csv = Class.create(pimcore.element.gridexport.abstract, {
    name: "csv",
    text: t("export_csv"),
    warningText: t('csv_object_export_warning'),

    getDownloadUrl: function(fileHandle) {
         return Routing.generate('pimcore_admin_dataobject_dataobjecthelper_downloadcsvfile', {fileHandle: fileHandle});
    },

    getObjectSettingsContainer: function () {
        var enableInheritance = new Ext.form.Checkbox({
            fieldLabel: t('enable_inheritance'),
            name: 'enableInheritance',
            inputValue: true,
            labelWidth: 200
        });

        return new Ext.form.FieldSet({
            title: t('object_settings'),
            items: [
                enableInheritance
            ]
        });
    },
    getExportSettingsContainer: function () {
        return new Ext.form.FieldSet({
            title: t('csv_settings'),
            items: [
                new Ext.form.TextField({
                    fieldLabel: t('delimiter'),
                    name: 'delimiter',
                    maxLength: 1,
                    labelWidth: 200,
                    value: ';'
                })
            ]
        });
    }
});

pimcore.globalmanager.get("pimcore.object.gridexport").push(new pimcore.object.gridexport.csv());
