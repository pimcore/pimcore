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

pimcore.registerNS("pimcore.object.gridexport.xlsx");
pimcore.object.gridexport.xlsx = Class.create(pimcore.element.gridexport.abstract, {
    name: "xlsx",
    text: t("export_xlsx"),

    getDownloadUrl: function(fileHandle) {
         return Routing.generate('pimcore_admin_dataobject_dataobjecthelper_downloadxlsxfile', {fileHandle: fileHandle});
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
    }
});

pimcore.globalmanager.get("pimcore.object.gridexport").push(new pimcore.object.gridexport.xlsx())
